<?php
	require 'config.php';
	require_once 'bitrix24-php-sdk/vendor/autoload.php';

	$config = new Config();
	$obB24App = new \Bitrix24\Bitrix24(false, null);
	$obB24App->setWebhookUrl($config->webhook);
	
	$id = $_GET["id"];
	
	$obB24Deal = new \Bitrix24\CRM\Deal\Deal($obB24App);
	$dealResponse = $obB24Deal->get($id);
	
	$obB24ProductRows = new \Bitrix24\CRM\Deal\ProductRows($obB24App);
	$productRowsResponse = $obB24ProductRows->get($id);
	
	$deal = $dealResponse["result"];
	$deal["COMMENTS"]="";
	$deal["productRows"] = $productRowsResponse["result"];
	
	$contactId = $deal["CONTACT_ID"];
	if($contactId){
		$obB24Contact = new \Bitrix24\CRM\Contact($obB24App);
		$contactResponse = $obB24Contact->get($contactId);
		$contact = $contactResponse["result"];
		$contact["COMMENTS"]="";
	
		$deal["contact"] = $contact;	
	}else{
		$companyId = $deal["COMPANY_ID"];
		if($companyId){
			$obB24Company = new \Bitrix24\CRM\Company($obB24App);
			$companyResponse = $obB24Company->get($companyId);
			$contact = $companyResponse["result"];
			$contact["COMMENTS"]="";
	
			$deal["contact"] = $contact;	
		}
	}
	
	echo json_encode($deal); 