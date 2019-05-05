<?php
require_once "class.entity-storage.php";
require_once "class.deal-product-rows-storage.php";

class CrmDealsHistoryStorage extends CrmEntityStorage{		
	function __construct($config,$logger) {
	   parent::__construct($config,$logger);
	}
	
	protected function getTableName(){
		return "crm_deals_history";
	}
	
	public function addDealToHistory($lead){
		$item = $lead;
		$item["DEAL_ID"] = $lead["ID"]; 
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