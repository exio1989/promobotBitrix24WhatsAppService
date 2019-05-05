<?php
 require_once 'class.whatsapp-sended-messages-storage.php';
 require_once 'class.whatsapp-client.php';

class WhatsappMessageSenderService{
	private $client;
	private $storage;
	private $logger;
	
	function __construct($config,$logger) {
	   $this->logger = $logger;
	   $this->client = new WhatsappClient($config->whatsApiApiUri,$config->whatsApiApiToken,$logger);
	   $this->storage = new WhatsAppSendedMessagesStorage($config,$logger); 
	}

	public function sendMessage($eventType,$phone,$message,$leadId){
		if($this->storage->isMessageSent($eventType,$phone)){
			$this->logger->info("Message already sent to ".$phone);
			return;
		}
	
		$response = $this->client->sendMessage($phone,$message);
			
		if($response!==FALSE){
			$chatApiMessageId = $response->id;
			$this->storage->addItem($chatApiMessageId,$eventType,$phone,$message,$leadId);
		}
	}
	
	public function setMessageDelivered($chatApiMessageId){
		$this->storage->setDelivered($chatApiMessageId);
	}
	
	public function setMessageViewed($chatApiMessageId){
		$this->storage->setViewed($chatApiMessageId);
	}
	
	public function setMessageSent($chatApiMessageId){
		$this->storage->setSent($chatApiMessageId);
	}
}