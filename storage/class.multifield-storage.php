<?php
require_once "class.entity-storage.php";

class CrmMultifieldStorage extends CrmEntityStorage{
	private $tableName;
	
	function __construct($tableName,$config,$logger) {
	   parent::__construct($config,$logger);
	   $this->tableName=$tableName;
	}
	
	protected function getTableName(){
		return $this->tableName;
	}  
	
	public function replaceItemsByOwnerId($ownerId,$items,$mysqli){	
		$rItems = array();
		foreach($items as $item){
			$rItem = array();
			foreach($item as $k => $v){
				$rItem[$k]=$v;
			}
			$rItem['OWNER_ID']=$ownerId;
			$rItems[]=$rItem;
		}
		
		$this->deleteItemsByOwnerId($ownerId,$mysqli);
		$this->addItemsInternal($rItems,$mysqli);
	}
	
	public function deleteItemsByOwnerId($ownerId,$mysqli){
		$mysqli->query("DELETE FROM ".$this->getTableName()." WHERE OWNER_ID=".intval($ownerId)); 	
	}
}