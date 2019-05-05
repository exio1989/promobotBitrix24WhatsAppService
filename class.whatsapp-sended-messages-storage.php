<?php
require_once "storage/class.entity-storage.php";

class WhatsAppSendedMessagesStorage extends CrmEntityStorage{
	function __construct($config,$logger) {
	   parent::__construct($config,$logger); 
	}
	
	protected function getTableName(){
		return "whatsapp_sended_messages";
	}
	
	public function addItem($chatApiMessageId,$eventType,$phone,$message,$leadId = FALSE, $dealId = FALSE){
		$item = array();
		$item["chat_api_message_id"] = $chatApiMessageId; 
		$item["phone"] = $phone; 
		$item["event_type"] = $eventType; 
		$item["message"] = $message; 
		$item["lead_id"] = $leadId; 
		$item["deal_id"] = $dealId; 
		$item["create_date"] = date("Y-m-d H:i:s");
			
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		$items = array();
		$items[] = $item;
		$this->addItemsInternal($items,$mysqli);
		$mysqli->commit();
		$mysqli->close();
	}	
	
	public function isMessageSent($eventType,$phone){
		$r = FALSE;
		
		$mysqli = $this->openConnection();
		if ($result = $mysqli->query("SELECT Id FROM ".$this->getTableName()." WHERE phone = '". $phone ."' AND event_type = '". $eventType ."' LIMIT 1")) {
			$r = mysqli_num_rows($result) > 0;
		}
		$mysqli->commit();
		$mysqli->close();
		
		return $r;
	}
	
	public function setDelivered($chatApiMessageId){
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		
		$mysqli->query("UPDATE ".$this->getTableName()." SET delivered_date = UTC_TIMESTAMP() WHERE chat_api_message_id='".$chatApiMessageId."'");
		
		$mysqli->commit();
		$mysqli->close();
	}
	
	public function setViewed($chatApiMessageId){
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		
		$mysqli->query("UPDATE ".$this->getTableName()." SET viewed_date = UTC_TIMESTAMP() WHERE chat_api_message_id='".$chatApiMessageId."'");
		
		$mysqli->commit();
		$mysqli->close();
	}
	
	public function setSent($chatApiMessageId){		
		$this->logger->info($chatApiMessageId);
		
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		
		$mysqli->query("UPDATE ".$this->getTableName()." SET sent_date = UTC_TIMESTAMP() WHERE chat_api_message_id='".$chatApiMessageId."'");
		
		$mysqli->commit();
		$mysqli->close();
	}
}