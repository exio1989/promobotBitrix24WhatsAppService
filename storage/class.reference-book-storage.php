<?php
require_once "class.entity-storage.php";

class CrmReferenceBookStorage extends CrmEntityStorage{	
	private $entityId;
	
	function __construct($entityId,$config,$logger) {
	   parent::__construct($config,$logger);
	   $this->entityId=$entityId;
	}
	
	protected function getTableName(){
		return "crm_reference_books";
	}
	
	public function addItems($items){
		$rItems = array();
		foreach($items as $item){
			$item["ENTITY_ID"]=$this->entityId;
			$rItems[] = $item;
		}
				
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);		
		$this->addItemsInternal($rItems,$mysqli);
		$mysqli->commit();
		$mysqli->close();
	}
	
	public function deleteAllItems(){
		$mysqli = $this->openConnection();
		$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		$mysqli->query("DELETE FROM ".$this->getTableName()." WHERE ENTITY_ID='".$this->entityId."'");
		$mysqli->commit();
		$mysqli->close();
	}
}