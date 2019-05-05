<?php
require_once "class.entity-storage.php";

class CrmCurrenciesStorage extends CrmEntityStorage{	
	
	protected function getTableName(){
		return "crm_currencies";
	}
	
	public function updateItem($item){
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		$this->updateItemInternal($item,$mysqli);
		$mysqli->commit();
		$mysqli->close();	
	}
	
	public function deleteItem($itemId){
		$mysqli = $this->openConnection();
		$this->deleteItemInternal($itemId,$mysqli);
		$mysqli->close();
	}
	
	public function getCurrencyValue($currency){
		$query = "SELECT * from " . $this->getTableName()." WHERE CURRENCY='".$currency."' LIMIT 1";

		$mysqli = $this->openConnection();
		
		if($result = $mysqli->query($query)){
			$r = $result->fetch_object();
		}
		$mysqli->close();
		return $r->AMOUNT;
	}
	
	protected function updateItemInternal($item,$mysqli){
		$this->deleteItemInternal($item["CURRENCY"],$mysqli);
		$this->addItemsInternal(array($item),$mysqli);
	}
	
	protected function deleteItemInternal($itemId,$mysqli){
		$mysqli->query("DELETE FROM ".$this->getTableName()." WHERE CURRENCY='".$itemId."'");
	}
}