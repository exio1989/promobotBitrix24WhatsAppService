<?php
require_once "class.entity-with-multifields-storage.php";
require_once "class.product-rows-storage.php";

class CrmLeadsHistoryStorage extends CrmEntityStorage{
	function __construct($config,$logger) {
	   parent::__construct($config,$logger);
	}
	
	protected function getTableName(){
		return "crm_leads_history";
	}
	
	public function addLeadToHistory($lead){
		$item = $lead;
		$item["LEAD_ID"] = $lead["ID"]; 
		unset($item["ID"]); 
			
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		$items = array();
		$items[] = $item;
		$this->addItemsInternal($items,$mysqli);
		$mysqli->commit();
		$mysqli->close();
	}	
}