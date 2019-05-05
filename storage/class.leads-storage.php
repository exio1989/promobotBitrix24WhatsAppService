<?php
require_once "class.entity-with-multifields-storage.php";
require_once "class.product-rows-storage.php";

class CrmLeadsStorage extends CrmEntityWithMultifieldStorage{
	private $productsStorage;

	function __construct($config,$logger) {
	   $multifieldPairs = array();
	   $multifieldPairs["EMAIL"] = "crm_leads_emails";
	   $multifieldPairs["PHONE"] = "crm_leads_phones";
	   parent::__construct($multifieldPairs,$config,$logger);
	   $this->productsStorage = new CrmProductRowsStorage("crm_leads_products",$config,$logger);
	}
	
	protected function getTableName(){
		return "crm_leads";
	}
	
	public function addItem($item){
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		$this->ewmAddItem($item,$mysqli);
		$this->productsStorage->replaceProductRows($item["ID"], $productRows,$mysqli);
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
		$this->productsStorage->replaceProductRows($item["ID"], $productRows,$mysqli);
		$mysqli->commit();
		$mysqli->close();	
	}
	
	public function deleteItem($itemId){
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		$this->ewmDeleteItem($itemId,$mysqli);
		$this->productsStorage->deleteProductRows($itemId,$mysqli);
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
	
	public function deleteAllProductRows(){
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		$this->productsStorage->deleteAllItemsInternal($mysqli);
		$mysqli->commit();
		$mysqli->close();
	}
	
	public function addProductRows($productRows){
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		$this->productsStorage->addItemsInternal($productRows,$mysqli);
		$mysqli->commit();
		$mysqli->close();
	}
}