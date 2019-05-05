<?php
require_once "class.entity-storage.php";
require_once "class.product-rows-storage.php";

class CrmInvoicesStorage extends CrmEntityStorage{	
	private $productsStorage;
	
	function __construct($config,$logger) {
	   parent::__construct($config,$logger);
	   $this->productsStorage = new CrmProductRowsStorage("crm_invoices_products",$config,$logger);
	}
	
	protected function getTableName(){
		return "crm_invoices";
	}
	
	public function addItem($item,$productRows){
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		$items = array();
		$items[] = $item;
		$this->addItemsInternal($items,$mysqli);
		$this->productsStorage->replaceProductRows($item["ID"], $productRows,$mysqli);
		$mysqli->commit();
		$mysqli->close();
	}

	public function updateItem($item,$productRows){		
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		$this->updateItemInternal($item,$mysqli);
		$this->productsStorage->replaceProductRows($item["ID"], $productRows,$mysqli);
		$mysqli->commit();
		$mysqli->close();
	}
	
	public function deleteItem($itemId){
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		$this->deleteItemInternal($itemId,$mysqli);
		$this->productsStorage->deleteProductRows($itemId,$mysqli);
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