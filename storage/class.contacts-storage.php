<?php
require_once "class.entity-with-multifields-storage.php";

class CrmContactsStorage extends CrmEntityWithMultifieldStorage{

	function __construct($config,$logger) {
	   $multifieldPairs = array();
	   $multifieldPairs["EMAIL"] = "crm_contacts_emails";
	   $multifieldPairs["PHONE"] = "crm_contacts_phones";
	   parent::__construct($multifieldPairs,$config,$logger);
	}
	
	protected function getTableName(){
		return "crm_contacts";
	}
	
	public function addItem($item){
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		$this->ewmAddItem($item,$mysqli);
		$mysqli->commit();
		$mysqli->close();
	}
	
	public function addItems($items){
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		$this->ewmAddItems($items,$mysqli);
		$mysqli->commit();
		$mysqli->close();
	}
	
	public function updateItem($item){
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		$this->ewmUpdateItem($item,$mysqli);
		$mysqli->commit();
		$mysqli->close();	
	}
	
	public function deleteItem($itemId){
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		$this->ewmDeleteItem($itemId,$mysqli);
		$mysqli->commit();
		$mysqli->close();
	}
	
	public function deleteAllItems(){
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		$this->ewmDeleteAllItems($mysqli);
		$mysqli->commit();
		$mysqli->close();
	}
}