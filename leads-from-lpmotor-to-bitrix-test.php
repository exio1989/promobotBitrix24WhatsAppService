<?php
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

function createLead($title,$name,$email,$sourceid,$responsibleId,$sourceDescription,$obB24App){
	$obB24Lead = new \Bitrix24\CRM\Lead($obB24App);
	$fields = array();	
	$fields["TITLE"]=$title;
    $fields["NAME"]=$name;
	$fields["ASSIGNED_BY_ID"]=$responsibleId;
    $fields["OPENED"]="Y"; 
	$fields["EMAIL"]=createMultifield($email,"WORK");
	$fields["SOURCE_ID"]=$sourceid; 
	$fields["SOURCE_DESCRIPTION"]=$sourceDescription; 
   $r = $obB24Lead->add($fields);
   echo json_encode($r);
}

use Bitrix24\CRM\Deal;

try {
	$obB24App = new \Bitrix24\Bitrix24(false, null);
	$obB24App->setWebhookUrl("https://b24-yenup6.bitrix24.com/rest/1/7lpe8u7cc4r3u8u9/");
	
	$name = $_REQUEST['name'];
	$email = $_REQUEST['email'];
	$sourceid = 'WEB';
	$sourceDescription = "Лид с ravvast.com\r\n Доп. поля:\r\n ".$_REQUEST['extra'];
	$title = "Лид с лпмотор(".$name.", ".$email.")";
	$responsibleEmail = "minaeva@ravvast.com";
	$responsibleId = getResponsibleIdByEmail($responsibleEmail,$obB24App);
	if($responsibleId===FALSE){
		echo "Пользователь minaeva@ravvast.com не найден в CRM!";
	}
	
	//$contactId = searchContactIdByEmail($email,$obB24App);
	
	createLead($title,$name,$email,$sourceid,$responsibleId,$sourceDescription, $obB24App);
} catch (Exception $e) {
	var_dump($e);
}

