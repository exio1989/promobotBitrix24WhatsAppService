<?php
 require_once 'config.php';
 require_once 'class.logger.php';
require 'bitrix24-php-sdk/vendor/autoload.php';

function createMultifield($value,$type){
	$field = array();
	$field["VALUE_TYPE"]=$type;
    $field["VALUE"]=$value; 
	
	$values = array();
	$values[] = $field;
	return $values;
}

function addContact($firstName,
					$lastName,
					$phone,
					$email,
					$country,
					$postcode,
					$address_1,
					$address_2,
					$city,
					$state,
					$obB24App){
	$obB24Contact = new \Bitrix24\CRM\Contact($obB24App);
   $fields = array();
    $fields["NAME"]=$firstName;
    $fields["LAST_NAME"]=$lastName;
    $fields["OPENED"]="Y"; 
    $fields["PHONE"]=createMultifield($phone,"WORK");
	$fields["EMAIL"]=createMultifield($email,"WORK");
	$fields["ADDRESS_COUNTRY_CODE"]=$country;
	$fields["ADDRESS_POSTAL_CODE"]=$postcode;
	$fields["ADDRESS"]=$address_1;
	$fields["ADDRESS_2"]=$address_2;
	$fields["ADDRESS_CITY"]=$city;
	$fields["ADDRESS_PROVINCE"]=$state;
   $obB24Contact->add($fields);
}

function searchContactIdByEmail($email,$obB24App){
	$obB24Contact = new \Bitrix24\CRM\Contact($obB24App);
	$filter = array();
	$filter["EMAIL"] = $email;
	$select = array();
	$select[]="ID";
	$response = $obB24Contact->getList($order = array(), $filter,$select);
	$result = $response["result"];
	if(count($result )==1){
		return $result[0]["ID"];
	}
	return FALSE;
}

function createDeal($obB24App){
	$obB24Deal = new \Bitrix24\CRM\Deal\Deal($obB24App);
	$fields = array();
    $fields[""]=$firstName;
    
   $obB24Contact->add($fields);
}

function getResponsibleIdByEmail($email,$obB24App){
	$obB24User = new Bitrix24\User\User($obB24App);
	$fields = array();	
	$fields["EMAIL"]=$email;
	$response = $obB24User->search($fields);
	$result = $response["result"];
	if(count($result )==1){
		return $result[0]["ID"];
	}
	return FALSE;
}

function createLead($title,$name,$email,$phone,$sourceid,$responsibleId,$sourceDescription,$originatorId,$originId,$obB24App){
	$obB24Lead = new \Bitrix24\CRM\Lead($obB24App);
	$fields = array();	
	$fields["TITLE"]=$title;
    $fields["NAME"]=$name;
	$fields["ASSIGNED_BY_ID"]=$responsibleId;
    $fields["OPENED"]="Y"; 
	if($email){
		$fields["EMAIL"]=createMultifield($email,"WORK");
	}
	if($phone){
		$fields["PHONE"]=createMultifield($phone,"WORK");
	}
	$fields["SOURCE_ID"]=$sourceid; 
	$fields["SOURCE_DESCRIPTION"]=$sourceDescription; 
	$fields["ORIGINATOR_ID"] = $originatorId;
	$fields["ORIGIN_ID"] = $originId;
   $r = $obB24Lead->add($fields);
   echo json_encode($r);
}

use Bitrix24\CRM\Deal;

try {
	$config = new Config();
    $logger = new Logger($config->logPath);
	
	$obB24App = new \Bitrix24\Bitrix24(false, null);
	$obB24App->setWebhookUrl($config->webhook);
	
	$name = isset($_REQUEST['name'])?$_REQUEST['name']:FALSE;
	$email = isset($_REQUEST['email'])?$_REQUEST['email']:FALSE;
	$phone = isset($_REQUEST['phone'])?$_REQUEST['phone']:FALSE;	
	$sourceid = isset($_REQUEST['bitrix_source'])?$_REQUEST['bitrix_source']:'WEB';
	$sourceDescription = "Лид с магазина".PHP_EOL;
			
	if(isset($_REQUEST['form'])){
		$sourceDescription.=" Форма: ".$_REQUEST['form'].PHP_EOL;
	}
	if(isset($_REQUEST['question'])){
		$sourceDescription.=" Вопрос: ".$_REQUEST['question'].PHP_EOL;
	}	
	if(isset($_REQUEST['product'])){
		$sourceDescription.=" Продукт: ".$_REQUEST['product'].PHP_EOL;
	}	
	if(isset($_REQUEST['products'])){
		$jsonProducts = urldecode($_REQUEST['products']);
		$products = json_decode($jsonProducts);
		
		$sourceDescription.=" Товары: ".PHP_EOL;
		foreach($products as $product){
			$sourceDescription.="---".$product->name." кол-во: ".$product->qty.PHP_EOL;
		}
	}	
	if(isset($_REQUEST['customerDestination'])){
		$jsonCustomerDestination = urldecode($_REQUEST['customerDestination']);
		$customerDestination = json_decode($jsonCustomerDestination);
		
		$sourceDescription.=" Страна: ".$customerDestination->country.PHP_EOL;
		$sourceDescription.=" Почтовый индекс: ".$customerDestination->postcode.PHP_EOL;
	}		
	if(isset($_REQUEST['country'])){
		$sourceDescription.=" Страна: ".$_REQUEST['country'].PHP_EOL;
	}	
	if(isset($_REQUEST['woocommerce_order_id'])){
		$sourceDescription.=" Номер заказа в магазине: ".$_REQUEST['woocommerce_order_id'].PHP_EOL;
	}	
	if(isset($_REQUEST['woocommerce_order_link'])){
		$sourceDescription.=" Ссылка на заказ: ".$_REQUEST['woocommerce_order_link'].PHP_EOL;
	}		
	if(isset($_REQUEST['ga_client_id'])){
		$originId = $_REQUEST['ga_client_id'];
	}
	if(isset($_REQUEST['shippingInfo'])){
		$sourceDescription.=$_REQUEST['shippingInfo'].PHP_EOL;
	}
	if(isset($_REQUEST['goodsInfo'])){
		$sourceDescription.=$_REQUEST['goodsInfo'].PHP_EOL;
	}
	if(isset($_REQUEST['caseIncluded'])){
		$sourceDescription.=" Сумка включена в заказ".PHP_EOL;
	}
	if(isset($_REQUEST['shippingPrice'])){
		$sourceDescription.=" Стоимость доставки: ".$_REQUEST['shippingPrice'].PHP_EOL;
	}
	if(isset($_REQUEST['shippingCountry'])){
		$sourceDescription.=" Страна: ".$_REQUEST['shippingCountry'].PHP_EOL;
	}
	if(isset($_REQUEST['currency'])){
		$sourceDescription.=" Валюта: ".$_REQUEST['currency'].PHP_EOL;
	}
	if(isset($_REQUEST['productPrice'])){
		$sourceDescription.=" Цена товара: ".$_REQUEST['productPrice'].PHP_EOL;
	}
	if(isset($_REQUEST['casePrice'])){
		$sourceDescription.=" Цена сумки: ".$_REQUEST['casePrice'].PHP_EOL;
	}
	if(isset($_REQUEST['totalPrice'])){
		$sourceDescription.=" ИТОГО: ".$_REQUEST['totalPrice'].PHP_EOL;
	}
	if(isset($_REQUEST['productName'])){
		$sourceDescription.=" Продукт: ".$_REQUEST['productName'].PHP_EOL;
	}
	if(isset($_REQUEST['postcode'])){
		$sourceDescription.=" Почтовый индекс: ".$_REQUEST['postcode'].PHP_EOL;
	}
	if(isset($_REQUEST['address'])){
		$sourceDescription.=" Адрес доставки: ".$_REQUEST['address'].PHP_EOL;
	}
	
		
	$title = "Лид с магазина(".$name.", ".$email.")";
	$responsibleEmail = "minaeva@ravvast.com";
	$responsibleId = getResponsibleIdByEmail($responsibleEmail,$obB24App);
	if($responsibleId===FALSE){
		echo "Пользователь minaeva@ravvast.com не найден в CRM!";
	}
	
	$originatorId = "ravshop";
	createLead($title,$name,$email,$phone,$sourceid,$responsibleId,$sourceDescription,$originatorId,$originId, $obB24App);
} catch (Exception $e) {
	var_dump($e);
	file_put_contents("test2.txt",json_encode($e));
}

