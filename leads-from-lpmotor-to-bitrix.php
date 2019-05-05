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

function createLead($title,$name,$email,$phone,$sourceid,$responsibleId,$sourceDescription,$obB24App){
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
   $r = $obB24Lead->add($fields);
   file_put_contents("t222.txt",json_encode($r).PHP_EOL,FILE_APPEND);
   echo json_encode($r);
}

use Bitrix24\CRM\Deal;

try {
	$config = new Config();
    $logger = new Logger($config->logPath);
	
	$obB24App = new \Bitrix24\Bitrix24(false, null);
	$obB24App->setWebhookUrl($config->webhook);
	
	$name = $_REQUEST['name'];
	$email = isset($_REQUEST['email'])?$_REQUEST['email']:FALSE;
	$phone = isset($_REQUEST['phone'])?$_REQUEST['phone']:FALSE;
	
	$tune = isset($_REQUEST['tune'])?$_REQUEST['tune']:FALSE;
	$case = isset($_REQUEST['case'])?$_REQUEST['case']:FALSE;
	$country = isset($_REQUEST['country'])?$_REQUEST['country']:FALSE;
	
	$sourceid = isset($_REQUEST['bitrix_source'])?$_REQUEST['bitrix_source']:'WEB';
	$sourceDescription = "Лид с ravvast.com".PHP_EOL
		." tune = ".$tune.PHP_EOL
		." case = ".$case.PHP_EOL
		." country = ".$country.PHP_EOL
		." доп. поля: ".$_REQUEST['extra'];
	$title = "Лид с лпмотор(".$name.", ".$email.")";
	$responsibleEmail = "minaeva@ravvast.com";
	$responsibleId = getResponsibleIdByEmail($responsibleEmail,$obB24App);
	if($responsibleId===FALSE){
		echo "Пользователь minaeva@ravvast.com не найден в CRM!";
	}
	
	//$contactId = searchContactIdByEmail($email,$obB24App);
	
	createLead($title,$name,$email,$phone,$sourceid,$responsibleId,$sourceDescription, $obB24App);
} catch (Exception $e) {
	var_dump($e);
}

