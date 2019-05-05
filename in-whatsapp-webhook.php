<?php
 require_once 'config.php';
 require_once 'class.logger.php';
 require_once 'class.psr-logger.php';
 require_once 'class.whatsapp-message-sender-service.php';

$config = new Config();
$logger = new PsrLogger(Config::logFilePath(),$config->mailForAlert,$config->alertMailSubject);

$postdata = file_get_contents("php://input");
$json = json_decode($postdata);
$service = new WhatsappMessageSenderService($config,$logger);

$acks = $json->ack;
foreach($acks as $ack){
	$messageId  = $ack->id;
	switch($ack->status){
		case "sent":
			$service->setMessageSent($messageId);
			break;
		case "delivered":
			$service->setMessageDelivered($messageId);
			break;
		case "viewed":
			$service->setMessageViewed($messageId);
			break;
	}
}