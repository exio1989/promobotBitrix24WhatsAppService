<?php
require_once 'bitrix24-php-sdk/vendor/autoload.php';
require_once 'storage/class.deals-storage.php';
require_once 'storage/class.contacts-storage.php';
require_once 'storage/class.invoices-storage.php';
require_once 'storage/class.leads-storage.php';
require_once 'storage/class.currencies-storage.php';
require_once 'storage/class.reference-book-storage.php';
require_once 'storage/class.products-storage.php';
require_once 'storage/class.users-storage.php';
require_once 'class.currency-entity.php';
require_once 'class.logger.php';
require_once 'class.whatsapp-events-sender-service.php';

class CrmExporter{
	private $delayBetweenRequestsMicroSec = 500000;
	private $itemsPerResponse = 50;
	private $obB24App;
	private $config;
	private $logger;
	private $eventsSenderService;
	
	function __construct($config,$logger) {
	   $this->config = $config;
	   $this->logger = $logger;
	   $this->obB24App = new \Bitrix24\Bitrix24(false, $logger);
	   $this->obB24App->setWebhookUrl($config->webhook);
	   $this->eventsSenderService = new WhatsappEventsSenderService($this->config,$this->logger);
	}
	
	public function exportAll(){
		$this->exportUsers();
		
		if($this->exportDeals() == FALSE)
		    if($this->exportDeals() == FALSE){
		        $this->logger->error("Не удалось экспортировать сделки из битрикс24 из-за дублей в ID");    
		        die();    
		    }
		        
		    
		if($this->exportLeads() == FALSE)
		    if($this->exportLeads() == FALSE){
		        $this->logger->error("Не удалось экспортировать лиды из битрикс24 из-за дублей в ID");    
		        die();    
		    }

		$this->exportCurrencies();
		$this->exportProducts();
		
		$obB24Status = new \Bitrix24\CRM\Status($this->obB24App);
		$referenceBooks = $obB24Status->entityTypes()["result"];
		foreach($referenceBooks as $referenceBook){
			$this->exportReferenceBook($referenceBook["ID"]);	
		}
		
		
		$this->exportContacts();
		$this->exportInvoices();
	}
	
	public function exportLastDays(){
		$this->exportUsers();
		
		if($this->exportDealsLast60Days() == FALSE)
		    if($this->exportDealsLast60Days() == FALSE){
		        $this->logger->error("Не удалось экспортировать сделки из битрикс24 из-за дублей в ID");    
		        die();    
		    }
		        
		    
		if($this->exportLeadsLast14Days() == FALSE)
		    if($this->exportLeadsLast14Days() == FALSE){
		        $this->logger->error("Не удалось экспортировать лиды из битрикс24 из-за дублей в ID");    
		        die();    
		    }

		$this->exportCurrencies();
		$this->exportProducts();
		
		$obB24Status = new \Bitrix24\CRM\Status($this->obB24App);
		$referenceBooks = $obB24Status->entityTypes()["result"];
		foreach($referenceBooks as $referenceBook){
			$this->exportReferenceBook($referenceBook["ID"]);	
		}
		
		
		$this->exportContacts();
		$this->exportInvoices();
	}
	
	private function exportReferenceBook($name){
		$referenceBookStorage = new CrmReferenceBookStorage($name,$this->config,$this->logger);	
		$referenceBookItems = $this->fetchReferenceBook($name);
		
		$referenceBookStorage->deleteAllItems();
		$referenceBookStorage->addItems($referenceBookItems);
	}
	
	private function exportCurrencies(){
		$currenciesStorage = new CrmCurrenciesStorage($this->config,$this->logger);	
		$currencies = $this->fetchAllCurrencies();
		
		$currenciesStorage->deleteAllItems();
		$currenciesStorage->addItems($currencies);
	}
	
	private function exportProducts(){
		$productsStorage = new CrmProductsStorage($this->config,$this->logger);	
		$products = $this->fetchAllProducts();
		
		$productsStorage->deleteAllItems();
		$productsStorage->addItems($products);
	}
	
	private function exportUsers(){
		$usersStorage = new CrmUsersStorage($this->config,$this->logger);	
		$users = $this->fetchAllUsers();
		
		$usersStorage->deleteAllItems();
		$usersStorage->addItems($users);
	}
	
	public function exportLeads(){
		$leadsStorage = new CrmLeadsStorage($this->config,$this->logger);	
		$leads = $this->fetchAllLeads();
		
		$leadsIds = array();
		foreach($leads as $lead){
			$leadsIds[] = $lead["ID"];
		}
		if($this->hasDuplicates($leadsIds))
		    return FALSE;
		    
		$productRows = $this->fetchProductRows("L",$leadsIds);
		
		$leadsStorage->addItems($leads);
		
		$leadsStorage->addProductRows($productRows);
		
		return TRUE;
	}
	
	public function exportLeadsLast14Days(){
		$leadsStorage = new CrmLeadsStorage($this->config,$this->logger);	
		$leads = $this->fetchLeadsLast14Days();
		
		$leadsIds = array();
		foreach($leads as $lead){
			$leadsIds[] = $lead["ID"];
		}
		if($this->hasDuplicates($leadsIds))
		    return FALSE;
		    
		$productRows = $this->fetchProductRows("L",$leadsIds);
		$leadsStorage->addItems($leads);
		$leadsStorage->addProductRows($productRows);
		
		foreach($leads as $lead){
			$this->eventsSenderService->sendEventOnLeadUpdate($lead);
		}
		
		return TRUE;
	}
	
	public function fetchLeadsLast14Days(){
		$obB24Lead = new \Bitrix24\CRM\Lead($this->obB24App);
		$totalItems = $this->getTotalEntities($obB24Lead);
		$select = array("*","EMAIL","PHONE","ADDRESS*","UTM_*","UF_*");
		
		$date14DaysBefore = time() - (14 * 24 * 60 * 60);
		$filter = array(">DATE_CREATE"=>date('Y-m-d\Th:m:s+00:00', $date14DaysBefore));
		
		$totalItems =  $obB24Lead->getList(array(),$filter,$select,$i)['total'];
		
		$i=0;
		while(true){
			$query = $obB24Lead->getListQuery(array(),$filter,$select,$i);
			$this->obB24App->addSimpleBatchCall($query);
	
			$i+=$this->itemsPerResponse;
			if($i>$totalItems){
				break;
			}
		} 
	
		return $this->batchCalls();
	}
	
	
	private function fetchAllLeads(){
		$obB24Lead = new \Bitrix24\CRM\Lead($this->obB24App);
		$totalItems = $this->getTotalEntities($obB24Lead);
		$select = array("*","EMAIL","PHONE","ADDRESS*","UTM_*","UF_*");
		$i=0;
		while(true){
			$query = $obB24Lead->getListQuery(array(),array(),$select,$i);
			$this->obB24App->addSimpleBatchCall($query);
	
			$i+=$this->itemsPerResponse;
			if($i>$totalItems){
				break;
			}
		} 
	
		return $this->batchCalls();
	}
	
	private function fetchLead($id){
		$obB24Lead = new \Bitrix24\CRM\Lead($this->obB24App);
		$select = array("*","EMAIL","PHONE","ADDRESS*","UTM_*","UF_*");
		return $obB24Lead->get($id);		
	}
	
	private function exportInvoices(){
		$invoicesStorage = new CrmInvoicesStorage($this->config,$this->logger);	
		$invoices = $this->fetchAllInvoices();
		$invoicesIds = array();
		foreach($invoices as $invoice){
			$invoicesIds[] = $invoice["ID"];
		}
		$productRows = $this->fetchProductRows("I",$invoicesIds);
		
		
		$invoicesStorage->deleteAllItems();
		$invoicesStorage->addItems($invoices);
		
		$invoicesStorage->deleteAllProductRows();
		$invoicesStorage->addProductRows($productRows);
	}
	
	private function exportContacts(){
		$contactsStorage = new CrmContactsStorage($this->config,$this->logger);	
		$contacts = $this->fetchAllContacts();
		$contactsStorage->deleteAllItems();
		$contactsStorage->addItems($contacts);
	}
	
	public function exportDeals(){
		$dealsStorage = new CrmDealsStorage($this->config,$this->logger);	
		$deals = $this->fetchAllDeals();
		$dealIds = array();
		foreach($deals as $deal){
			$dealIds[] = $deal["ID"];
		}
		if($this->hasDuplicates($dealIds))
		    return FALSE;
		    
		$productRows = $this->fetchDealsProductRows($dealIds);
		
		
		$dealsStorage->addItems($deals);
		
		$dealsStorage->addProductRows($productRows);
		
		return TRUE;
	}
	
	public function exportDealsLast60Days(){
		$dealsStorage = new CrmDealsStorage($this->config,$this->logger);	
		$deals = $this->fetchDealsLast60Days();
		$dealIds = array();
		foreach($deals as $deal){
			$dealIds[] = $deal["ID"];
		}
		if($this->hasDuplicates($dealIds))
		    return FALSE;
		    
		$productRows = $this->fetchDealsProductRows($dealIds);
		
		
		$dealsStorage->addItems($deals);
		
		$dealsStorage->addProductRows($productRows);
		
		return TRUE;
	}	
	
	
	public function fetchDealsLast60Days(){
		$obB24Deal = new \Bitrix24\CRM\Deal\Deal($this->obB24App);
		$select = array("ID","TITLE","TYPE_ID","STAGE_ID","PROBABILITY","CURRENCY_ID",
						"OPPORTUNITY","TAX_VALUE","LEAD_ID","COMPANY_ID","CONTACT_ID",
						"QUOTE_ID","BEGINDATE","CLOSEDATE","ASSIGNED_BY_ID","CREATED_BY_ID",
						"MODIFY_BY_ID","DATE_CREATE","DATE_MODIFY","OPENED","CLOSED",
						"ADDITIONAL_INFO","LOCATION_ID","CATEGORY_ID","STAGE_SEMANTIC_ID",
						"IS_NEW","IS_RECURRING","ORIGINATOR_ID","ORIGIN_ID","UTM_SOURCE",
						"UTM_MEDIUM","UTM_CAMPAIGN","UTM_CONTENT","UTM_TERM","UF_*");
						
		$date60DaysBefore = time() - (60 * 24 * 60 * 60);
		$filter = array(">DATE_CREATE"=>date('Y-m-d\Th:m:s+00:00', $date60DaysBefore));
		
		$totalItems =  $obB24Deal->getList(array(),$filter,$select,$i)['total'];
		
		$i=0;
		while(true){
			$query = $obB24Deal->getListQuery(array(),$filter,$select,$i);
			$this->obB24App->addSimpleBatchCall($query);
	
			$i+=$this->itemsPerResponse;
			if($i>$totalItems){
				break;
			}
		}
	
		return $this->batchCalls();
	}
	
	private function fetchAllDeals(){
		$obB24Deal = new \Bitrix24\CRM\Deal\Deal($this->obB24App);
		$totalItems = $this->getTotalEntities($obB24Deal);
		$select = array("ID","TITLE","TYPE_ID","STAGE_ID","PROBABILITY","CURRENCY_ID",
						"OPPORTUNITY","TAX_VALUE","LEAD_ID","COMPANY_ID","CONTACT_ID",
						"QUOTE_ID","BEGINDATE","CLOSEDATE","ASSIGNED_BY_ID","CREATED_BY_ID",
						"MODIFY_BY_ID","DATE_CREATE","DATE_MODIFY","OPENED","CLOSED",
						"ADDITIONAL_INFO","LOCATION_ID","CATEGORY_ID","STAGE_SEMANTIC_ID",
						"IS_NEW","IS_RECURRING","ORIGINATOR_ID","ORIGIN_ID","UTM_SOURCE",
						"UTM_MEDIUM","UTM_CAMPAIGN","UTM_CONTENT","UTM_TERM","UF_*");
		$i=0;
		while(true){
			$query = $obB24Deal->getListQuery(array(),array(),$select,$i);
			$this->obB24App->addSimpleBatchCall($query);
	
			$i+=$this->itemsPerResponse;
			if($i>$totalItems){
				break;
			}
		}
	
		return $this->batchCalls();
	}

	private function fetchAllContacts(){
		$obB24Contact = new \Bitrix24\CRM\Contact($this->obB24App);
		$totalItems = $this->getTotalEntities($obB24Contact);
		$select = array("NAME","SECOND_NAME","LAST_NAME","LEAD_ID","TYPE_ID",
					"SOURCE_ID","SOURCE_DESCRIPTION","COMPANY_ID","EMAIL",
					"PHONE","DATE_CREATE","DATE_MODIFY","ADDRESS*","UTM_*","UF_*");
		$i=0;
		while(true){
			$query = $obB24Contact->getListQuery(array(),array(),$select,$i);
			$this->obB24App->addSimpleBatchCall($query);
	
			$i+=$this->itemsPerResponse;
			if($i>$totalItems){
				break;
			}
		} 
	
		return $this->batchCalls();
	}
	
	
	private function fetchReferenceBook($name){
		$obB24Status = new \Bitrix24\CRM\Status($this->obB24App);
		return $obB24Status->entityItems($name)["result"];
	}
	
	private function fetchAllProducts(){
		$obB24Product = new \Bitrix24\CRM\Product($this->obB24App);
		return $obB24Product->getList()["result"];
	}
	
	private function fetchAllUsers(){
		$obB24User = new \Bitrix24\User\User($this->obB24App);
		return $obB24User->search()["result"];
	}
	
	private function fetchAllCurrencies(){
		$obB24Currency = new CurrencyEntity($this->obB24App);
		$select = array("*");
		$i=0;
		while(true){
			$query = $obB24Currency->getListQuery(array(),array(),$select,$i);
			$this->obB24App->addSimpleBatchCall($query);
	
			$i+=$this->itemsPerResponse;
			if($i>$totalItems){
				break;
			}
		} 
	
		return $this->batchCalls();
	}

	private function fetchAllInvoices(){
		$obB24Invoice = new \Bitrix24\CRM\Invoice($this->obB24App);
		$totalItems = $this->getTotalEntities($obB24Invoice);
		$select = array("*","UF_*");
		$i=0;
		while(true){
			$query = $obB24Invoice->getListQuery(array(),array(),$select,$i);
			$this->obB24App->addSimpleBatchCall($query);
	
			$i+=$this->itemsPerResponse;
			if($i>$totalItems){
				break;
			}
		} 
	
		return $this->batchCalls();
	}

	private function fetchDealsProductRows($ids){
		$obB24ProductRows = new \Bitrix24\CRM\Deal\ProductRows($this->obB24App);
		foreach($ids as $id){
			$query = $obB24ProductRows->getQuery($id);
			$this->obB24App->addSimpleBatchCall($query);
		}
	
		return $this->batchCalls();
	}
	
	private function fetchProductRows($ownerType, $ids){
		$obB24ProductRows = new \Bitrix24\CRM\ProductRow($this->obB24App);
		foreach($ids as $id){
			$query = $obB24ProductRows->getListQuery($ownerType, $id);
			$this->obB24App->addSimpleBatchCall($query);
		}
	
		return $this->batchCalls();
	}
	
	private function getTotalEntities($obB24Entity){
		$response = $obB24Entity->getList();
		return intval($response["total"]);
	}

	private function batchCalls(){
		$batchResponse = $this->obB24App->processSimpleBatchCalls(0,$this->delayBetweenRequestsMicroSec);
	
		$items = array();
		foreach($batchResponse["result"] as $k=>$vItems){
			foreach($vItems as $vItem){
				$items[]=$vItem;
			}
		}
		return $items;
	}
	
	private function hasDuplicates($arr){
	    return count(array_unique($arr))<count($arr);
	}
}