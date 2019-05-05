<?php
require_once $_SERVER["DOCUMENT_ROOT"].'/config.php';
require_once $_SERVER["DOCUMENT_ROOT"].'/logger.php';

class LeadFromShop{
	public $id;
	public $title;
	public $customer_name;
	public $customer_email;
	public $phone;
	public $bitrix_source_id;
	public $responsible_email;
	public $description	originator_id;
	public $origin_id;
	public $ga_client_id;
	public $shipping_info;
	public $goods_info;
	public $case_included;
	public $shipping_price;
	public $shipping_country;
	public $currency;
	public $product_price;
	public $case_price;
	public $total_price;
	public $product_name;
	public $postcode;
	public $address;
	public $sended_to_bitrix;
}

class PaypalInvoicesStorage{
	private $tableName = "shp_leads_from_shop";
	private $dbHost;
	private $dbName;
	private $dbUser;
	private $dbPass;
	
	function __construct($dbHost,$dbName,$dbUser,$dbPass,$logger) {
	   $this->dbHost = $dbHost;
       $this->dbName = $dbName;
	   $this->dbUser = $dbUser;
	   $this->dbPass = $dbPass;
	   $this->logger = $logger;
	}
	
	public function getList(){
		return $this->getListInternal("1=1");
	}
	
	public function getPaidWithoutCheckInvoicesLast3Days(){
		return $this->getListInternal("paypal_invoice_create_date > DATE_ADD(NOW(), INTERVAL -3 DAY) AND atoll_uuid IS NULL AND paypal_invoice_status = 'PAID'");
	}
	
	public function getNotPaidInvoicesLast3Days(){
		return $this->getListInternal("paypal_invoice_create_date > DATE_ADD(NOW(), INTERVAL -3 DAY) AND paypal_invoice_status <> 'PAID' AND paypal_invoice_status <> 'REFUNDED' AND paypal_invoice_status <> 'CANCELLED'");
	}
	
	public function getNotPaidInvoicesAfter3DaysAgo(){
		return $this->getListInternal("paypal_invoice_create_date <= DATE_ADD(NOW(), INTERVAL -3 DAY) AND paypal_invoice_status <> 'PAID' AND paypal_invoice_status <> 'REFUNDED' AND paypal_invoice_status <> 'CANCELLED'");
	}
	
	public function getAtollWaitInvoicesLast3Days(){
		return $this->getListInternal("paypal_invoice_create_date > DATE_ADD(NOW(), INTERVAL -3 DAY) AND atoll_status = 'wait'");
	}
	
	private function getListInternal($whereClause){
		$r = array();
		
		$mysqli = $this->openConnection();		
		
		$sql = "SELECT id,deal_id,paypal_invoice_id,"
		."paypal_invoice_status,atoll_uuid,atoll_status,paypal_invoice_create_date,"
		."atoll_create_date,atoll_retry_count,sum,"
		."payment_purpose,currency,customer_email,"
		."paypal_invoice_paid_date,atoll_sell_date, "
		."valute_date,valute_value, paid_currency, paid_sum, converted_sum_in_rubles "
		." FROM ".$this->tableName." WHERE ".$whereClause." ORDER BY id desc";
		if ($stmt = $mysqli->prepare($sql)) {
			$stmt->execute();
			$stmt->bind_result($id,
								$deal_id,
								$paypal_invoice_id,
								$paypal_invoice_status,
								$atoll_uuid,
								$atoll_status,
								$paypal_invoice_create_date,
								$atoll_create_date,
								$atoll_retry_count,
								$sum,
								$payment_purpose,
								$currency,
								$customer_email,
								$paypal_invoice_paid_date,
								$atoll_sell_date,
								$valute_date,
								$valute_value,
								$paid_currency,
								$paid_sum,
								$convertedSumInRubles);
			while ($stmt->fetch()) {
				$invoice = new PaypalInvoice();
				$invoice->id = $id;
				$invoice->deal_id = $deal_id;
				
				$invoice->sum = $sum;
				$invoice->payment_purpose = $payment_purpose;
				$invoice->currency = $currency;
				$invoice->customer_email = $customer_email;
				
				$invoice->atoll_sell_date = $atoll_sell_date;
				$invoice->atoll_create_date = $atoll_create_date;
				$invoice->atoll_retry_count = $atoll_retry_count;
				$invoice->atoll_uuid = $atoll_uuid;
				$invoice->atoll_status = $atoll_status;
				
				$invoice->paypal_invoice_id = $paypal_invoice_id;
				$invoice->paypal_invoice_status = $paypal_invoice_status;
				$invoice->paypal_invoice_create_date = $paypal_invoice_create_date;
				$invoice->paypal_invoice_paid_date = $paypal_invoice_paid_date;
				$invoice->valute_date = $valute_date;
				$invoice->valute_value = $valute_value;
				$invoice->paid_currency = $paid_currency;
				$invoice->paid_sum = $paid_sum;
				$invoice->convertedSumInRubles = $convertedSumInRubles;
				$r[] = $invoice;
			}		
			$stmt->close();
		}else{
			$this->logger->putLog("getList Не удалось выполнить запрос: ".$mysqli->error);
			exit();
		}
		
		$mysqli->close();
		
		return $r;
	}
	
	public function add($deal_id,
						$paypal_invoice_id,
						$paypal_invoice_status,
						$paypal_invoice_create_date,
						$sum,
						$payment_purpose,
						$currency,
						$customer_email){
		$mysqli = $this->openConnection();
		
		$sql = "INSERT INTO ".$this->tableName
										." (deal_id, paypal_invoice_id, paypal_invoice_status, paypal_invoice_create_date, sum, payment_purpose, currency, customer_email)"
										." VALUES(?,?,?,?,?,?,?,?)";
		if ($stmt = $mysqli->prepare($sql)) {
			$stmt->bind_param("ssssssss", 
					$deal_id,
					$paypal_invoice_id,
					$paypal_invoice_status,
					$paypal_invoice_create_date,
					$sum,
					$payment_purpose,
					$currency,
					$customer_email);
			if(!$stmt->execute()){
				return FALSE;
			}
			$stmt->close();
		}else{
			$this->logger->putLog("add Не удалось выполнить запрос: ".$mysqli->error);
			return FALSE;
		}
		
		$mysqli->close();
		return TRUE;
	}
	
	public function setInvoiceStatus($paypal_invoice_id,$paypal_invoice_status){
		$mysqli = $this->openConnection();
		
		$sql = "UPDATE ".$this->tableName
										." SET paypal_invoice_status = ?"
										." WHERE paypal_invoice_id = ?";
		if ($stmt = $mysqli->prepare($sql)) {
			$stmt->bind_param("ss", $paypal_invoice_status, $paypal_invoice_id);
			$stmt->execute();		
			$stmt->close();
		}else{
			$this->logger->putLog("setInvoiceStatus Не удалось выполнить запрос: ".$mysqli->error);
			return FALSE;
		}
		
		$mysqli->close();
		return TRUE;
	}
	
	public function setInvoicePaid($paypal_invoice_id,$paypal_invoice_status,$paypal_invoice_paid_date){
		$mysqli = $this->openConnection();
		
		$sql = "UPDATE ".$this->tableName
										." SET paypal_invoice_status = ?, paypal_invoice_paid_date = ?"
										." WHERE paypal_invoice_id = ?";
		if ($stmt = $mysqli->prepare($sql)) {
			$stmt->bind_param("sss", $paypal_invoice_status, $paypal_invoice_paid_date, $paypal_invoice_id);
			$stmt->execute();		
			$stmt->close();
		}else{
			$this->logger->putLog("setInvoicePaid Не удалось выполнить запрос: ".$mysqli->error);
			return FALSE;
		}
		
		$mysqli->close();
		return TRUE;
	}
		
	public function createAtollCheck($id,$atoll_uuid,$atoll_status,$valute_date,$valute_value,$paid_currency,$paid_sum,$convertedSumInRubles){	
		$mysqli = $this->openConnection();
		
		$sql = "UPDATE ".$this->tableName
										." SET atoll_uuid = ?,atoll_status = ?, atoll_create_date = NOW(),valute_date = ?,valute_value = ?, paid_currency = ?, paid_sum = ?, converted_sum_in_rubles = ?"
										." WHERE id=?";
		if ($stmt = $mysqli->prepare($sql)) {
			$stmt->bind_param("sssssssi", $atoll_uuid,$atoll_status,$valute_date,$valute_value,$paid_currency,$paid_sum,$convertedSumInRubles,$id);
			$stmt->execute();		
			$stmt->close();
		}else{
			$this->logger->putLog("createAtollCheck Не удалось выполнить запрос: ".$mysqli->error);
			return FALSE;
		}
		
		$mysqli->close();
		return TRUE;
	}
	
	public function incRetrySendAtollCheck($id){	
		$mysqli = $this->openConnection();
		
		$sql = "UPDATE ".$this->tableName
										." SET atoll_retry_count = atoll_retry_count+1"
										." WHERE id = ?";
		if ($stmt = $mysqli->prepare($sql)) {
			$stmt->bind_param("i", $id);
			$stmt->execute();		
			$stmt->close();
		}else{
			$this->logger->putLog("incRetrySendAtollCheck Не удалось выполнить запрос: ".$mysqli->error);
			return FALSE;
		}
		
		$mysqli->close();
		return TRUE;
	}
	
	public function setAtollCheckDone($atoll_uuid,$atoll_status,$atoll_sell_date){
		$mysqli = $this->openConnection();
		
		$sql = "UPDATE ".$this->tableName
										." SET atoll_status = ?, atoll_sell_date = ?"
										." WHERE atoll_uuid = ?";
		if ($stmt = $mysqli->prepare($sql)) {
			$stmt->bind_param("sss", $atoll_status,$atoll_sell_date, $atoll_uuid);
			$stmt->execute();		
			$stmt->close();
		}else{
			$this->logger->putLog("setAtollCheckDone Не удалось выполнить запрос: ".$mysqli->error);
			return FALSE;
		}
		
		$mysqli->close();
		return TRUE;
	}
		
	private function openConnection(){
		$mysqli = new mysqli($this->dbHost, $this->dbUser, $this->dbPass, $this->dbName);

		/* проверка соединения */
		if ($mysqli->connect_errno) {
			$this->logger->putLog("Не удалось подключиться: ".$mysqli->connect_error);
			exit();
		}
		
		return $mysqli;
	}
}
