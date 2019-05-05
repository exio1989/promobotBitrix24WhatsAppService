<?php
 require_once 'class.whatsapp-message-sender-service.php';

class WhatsappEventsSenderService{
	private $logger;
	private $messageService;
	
	function __construct($config,$logger) {
	   $this->logger = $logger;
	   $this->messageService = new WhatsappMessageSenderService($config,$logger); 
	}

	public function sendEventOnLeadUpdate($lead){
		$eventType = "OnLeadUpdate";
		$message = "Привет, Вася! Лид изменен.";
		$phone = $this->getPhoneFromLead($lead);
		if($phone == FALSE){
			return;
		}			
		$this->messageService->sendMessage($eventType,$phone,$message,$lead["ID"]);
	}
	
	public function sendEventOnLeadAdd($lead){
		$eventType = "OnLeadAdd";
		$message = "Привет, Вася! Лид добавлен.";
		$phone = $this->getPhoneFromLead($lead);
		if($phone == FALSE){
			return;
		}				
		$this->messageService->sendMessage($eventType,$phone,$message,$lead["ID"]);
	}
	
	private function getPhoneFromLead($lead){
		if($lead["PHONE"] && count($lead["PHONE"]) > 0){
			return $lead["PHONE"][0]["VALUE"];
		}
	
		return FALSE;	 
	}
}