<?php
 require_once 'config.php';
 require_once 'class.logger.php';
 require_once 'bitrix24-php-sdk/vendor/autoload.php';
 require_once 'storage/class.deals-storage.php';
 require_once 'storage/class.leads-storage.php';
 require_once 'storage/class.deals-history-storage.php';
 require_once 'storage/class.leads-history-storage.php';
 require_once 'storage/class.invoices-storage.php';
 require_once "storage/class.contacts-storage.php";
 require_once 'storage/class.currencies-storage.php';
 require_once 'class.currency-entity.php';
 require_once 'class.psr-logger.php';
 require_once 'class.whatsapp-events-sender-service.php';
 
 $hasData = isset($_REQUEST['data'])
	  && isset($_REQUEST['data']['FIELDS'])
	  && isset($_REQUEST['data']['FIELDS']['ID']);
	  
$hasTestData = isset($_REQUEST['testdata']);
 
 if(!isset($_REQUEST['event']) 
	 || (!$hasData && !$hasTestData)){
	exit();
 }
 
 $config = new Config();
$logger = new PsrLogger(Config::logFilePath(),$config->mailForAlert,$config->alertMailSubject);
 
 $event = $_REQUEST['event'];
 $entityId = isset($_REQUEST['testdata'])?$_REQUEST['testdata']:$_REQUEST['data']['FIELDS']['ID'];
 $logger->Info("START webhook ".$event." ".$entityId);
 	
try{	
 $obB24App = new \Bitrix24\Bitrix24(false, null);
 $obB24App->setWebhookUrl($config->webhook);
 $eventsSenderService = new WhatsappEventsSenderService($config,$logger);
 
 switch($event){
	case 'ONCRMCONTACTADD'://Создание контакта
		$obB24Contact = new \Bitrix24\CRM\Contact($obB24App);
		$contactsStorage = new CrmContactsStorage($config,$logger);
		
		$responseContact = $obB24Contact->get($entityId);
		if(isset($responseContact) 
			&& isset($responseContact["result"])){
			$contact = $responseContact["result"];
			$contactsStorage->addItem($contact);
		} 
		break;
	case 'ONCRMCONTACTUPDATE'://Обновление контакта
		$obB24Contact = new \Bitrix24\CRM\Contact($obB24App);
		$contactsStorage = new CrmContactsStorage($config,$logger);
		
		$responseContact = $obB24Contact->get($entityId);
		if(isset($responseContact) 
			&& isset($responseContact["result"])){
			$contact = $responseContact["result"];
			$contactsStorage->updateItem($contact);
		}
		break;
	case 'ONCRMCONTACTDELETE'://Удаление контакта
		$contactsStorage = new CrmContactsStorage($config,$logger);
		
		$contactsStorage->deleteItem($entityId);
		break;
		
	
	case 'ONCRMCOMPANYADD'://Создание компании
		//не используется в CRM
		break;
	case 'ONCRMCOMPANYUPDATE'://Обновление компании
		//не используется в CRM
		break;
	case 'ONCRMCOMPANYDELETE'://Удаление компании
		//не используется в CRM
		break;
		
		
	case 'ONCRMDEALADD'://Создание сделки
		$obB24Deal = new \Bitrix24\CRM\Deal\Deal($obB24App);
		$obB24ProductRow = new \Bitrix24\CRM\Deal\ProductRows($obB24App);
		$dealsStorage = new CrmDealsStorage($config,$logger);
		$dealsHistoryStorage = new CrmDealsHistoryStorage($config,$logger);
 
		$responseDeal = $obB24Deal->get($entityId);
		$responseProductRow = $obB24ProductRow->get($entityId);
		if(isset($responseDeal) 
			&& isset($responseDeal["result"])
			&& isset($responseProductRow) 
			&& isset($responseProductRow["result"])){
			$deal = $responseDeal["result"];
			$productRows = $responseProductRow["result"];
			$dealsStorage->addItem($deal,$productRows);
			$dealsHistoryStorage->addDealToHistory($deal);
		} 
		break;
	case 'ONCRMDEALUPDATE'://Обновление сделки
		$obB24Deal = new \Bitrix24\CRM\Deal\Deal($obB24App);
		$obB24ProductRow = new \Bitrix24\CRM\Deal\ProductRows($obB24App);
		$dealsStorage = new CrmDealsStorage($config,$logger);
		$dealsHistoryStorage = new CrmDealsHistoryStorage($config,$logger);
 
		$responseDeal = $obB24Deal->get($entityId);
		$responseProductRow = $obB24ProductRow->get($entityId);
		if(isset($responseDeal) 
			&& isset($responseDeal["result"])
			&& isset($responseProductRow) 
			&& isset($responseProductRow["result"])){
			$deal = $responseDeal["result"];
			$productRows = $responseProductRow["result"];
			$dealsStorage->updateItem($deal,$productRows);
			$dealsHistoryStorage->addDealToHistory($deal);
		}
		break;
	case 'ONCRMDEALDELETE'://Удаление сделки
		$dealsStorage = new CrmDealsStorage($config,$logger);
 
		$dealsStorage->deleteItem($entityId);
		break;
		
	
	case 'ONCRMLEADADD'://Создание лида
		$obB24Lead = new \Bitrix24\CRM\Lead($obB24App);
		$obB24ProductRow = new \Bitrix24\CRM\ProductRow($obB24App);
		$leadsStorage = new CrmLeadsStorage($config,$logger);
		$leadsHistoryStorage = new CrmLeadsHistoryStorage($config,$logger);
 
		$responseLead = $obB24Lead->get($entityId);
		$responseProductRow = $obB24ProductRow->getList("L", $entityId);
		if(isset($responseLead) 
			&& isset($responseLead["result"])
			&& isset($responseProductRow) 
			&& isset($responseProductRow["result"])){
			$lead = $responseLead["result"];
			$productRows = $responseProductRow["result"];
			$leadsStorage->addItem($lead,$productRows);
			$leadsHistoryStorage->addLeadToHistory($lead);
			
			$eventsSenderService->sendEventOnLeadAdd($lead);
		} 
		break;
	case 'ONCRMLEADUPDATE'://Обновление лида
		$obB24Lead = new \Bitrix24\CRM\Lead($obB24App);
		$obB24ProductRow = new \Bitrix24\CRM\ProductRow($obB24App);
		$leadsStorage = new CrmLeadsStorage($config,$logger);
		$leadsHistoryStorage = new CrmLeadsHistoryStorage($config,$logger);
 
		$responseLead = $obB24Lead->get($entityId);
		$responseProductRow = $obB24ProductRow->getList("L", $entityId);
		if(isset($responseLead) 
			&& isset($responseLead["result"])
			&& isset($responseProductRow) 
			&& isset($responseProductRow["result"])){
			$lead = $responseLead["result"];
			$productRows = $responseProductRow["result"];
			$leadsStorage->updateItem($lead,$productRows);			
			$leadsHistoryStorage->addLeadToHistory($lead);
			
			$eventsSenderService->sendEventOnLeadUpdate($lead);
		}		
		break;	
	case 'ONCRMLEADDELETE'://Удаление лида 
		$leadsStorage = new CrmLeadsStorage($config,$logger);
 
		$leadsStorage->deleteItem($entityId);
		break;
		
		
	case 'ONCRMINVOICEADD'://Создание счета
		$obB24Invoice = new \Bitrix24\CRM\Invoice($obB24App);
		$obB24ProductRow = new \Bitrix24\CRM\ProductRow($obB24App);
		$invoicesStorage = new CrmInvoicesStorage($config,$logger);
 
		$responseInvoice = $obB24Invoice->get($entityId);
		$responseProductRow = $obB24ProductRow->getList("I", $entityId);
		if(isset($responseInvoice) 
			&& isset($responseInvoice["result"])
			&& isset($responseProductRow) 
			&& isset($responseProductRow["result"])){
			$invoice = $responseInvoice["result"];
			$productRows = $responseProductRow["result"];
			$invoicesStorage->addItem($invoice,$productRows);
		} 
		break;
	case 'ONCRMINVOICEUPDATE'://Обновление счета
		$obB24Invoice = new \Bitrix24\CRM\Invoice($obB24App);
		$obB24ProductRow = new \Bitrix24\CRM\ProductRow($obB24App);
		$invoicesStorage = new CrmInvoicesStorage($config,$logger);
 
		$responseInvoice = $obB24Invoice->get($entityId);
		$responseProductRow = $obB24ProductRow->getList("I", $entityId);
		if(isset($responseInvoice) 
			&& isset($responseInvoice["result"])
			&& isset($responseProductRow) 
			&& isset($responseProductRow["result"])){
			$invoice = $responseInvoice["result"];
			$productRows = $responseProductRow["result"];
			$invoicesStorage->updateItem($invoice,$productRows); 
		}
		break;
	case 'ONCRMINVOICEDELETE'://Удаление счета
		$invoicesStorage = new CrmInvoicesStorage($config,$logger);
 
		$invoicesStorage->deleteItem($entityId);
		break;
	case 'ONCRMINVOICESETSTATUS'://Обновление статуса счета
		$obB24Invoice = new \Bitrix24\CRM\Invoice($obB24App);
		$obB24ProductRow = new \Bitrix24\CRM\ProductRow($obB24App);
		$invoicesStorage = new CrmInvoicesStorage($config,$logger);
 
		$responseInvoice = $obB24Invoice->get($entityId);
		$responseProductRow = $obB24ProductRow->getList("I", $entityId);
		if(isset($responseInvoice) 
			&& isset($responseInvoice["result"])
			&& isset($responseProductRow) 
			&& isset($responseProductRow["result"])){
			$invoice = $responseInvoice["result"];
			$productRows = $responseProductRow["result"];
			$invoicesStorage->updateItem($invoice,$productRows); 
		}
		break;
		
		
	case 'ONCRMCURRENCYADD'://Создание валюты
		$obB24Currency = new CurrencyEntity($obB24App);
		$currenciesStorage = new CrmCurrenciesStorage($config,$logger);	
		
		$responseCurrency = $obB24Currency->get($entityId);
		if(isset($responseCurrency) 
			&& isset($responseCurrency["result"])){
			$сurrency = $responseCurrency["result"];
			$currenciesStorage->addItem($сurrency);
		} 
		break;
	case 'ONCRMCURRENCYUPDATE'://Обновление валюты
		$obB24Currency = new CurrencyEntity($obB24App);
		$currenciesStorage = new CrmCurrenciesStorage($config,$logger);	
		
		$responseCurrency = $obB24Currency->get($entityId);
		if(isset($responseCurrency) 
			&& isset($responseCurrency["result"])){
			$сurrency = $responseCurrency["result"];
			$currenciesStorage->updateItem($сurrency);
		}
		break;
	case 'ONCRMCURRENCYDELETE'://Удаление валюты
		$currenciesStorage = new CrmCurrenciesStorage($config,$logger);	
		
		$currenciesStorage->deleteItem($entityId);
		break;
 }
}
catch(Exception $ex){
	$logger->Info($ex->getMessage());
}
 $logger->Info("END webhook ".$event." ".$entityId);