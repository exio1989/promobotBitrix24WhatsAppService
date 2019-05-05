<?php
require_once "class.entity-storage.php";

class CrmProductRowsStorage extends CrmEntityStorage{
	private $tableName;
	
	function __construct($tableName,$config,$logger) {
	   parent::__construct($config,$logger);
	   $this->tableName = $tableName;
	}
	
	protected function getTableName(){
		return $this->tableName;
	}
	
	public function replaceProductRows($ownerId, $productRows,$mysqli){
		$rItems = array();
		foreach($productRows as $productRow){
			$rItem = array();
			foreach($productRow as $k => $v){
				$rItem[$k]=$v;
			}
			$rItem['OWNER_ID']=$ownerId;
			$rItems[]=$rItem;
		}
		$this->deleteProductRows(intval($ownerId), $mysqli);
		$this->addItemsInternal($rItems, $mysqli);
	} 
	
	public function deleteProductRows($ownerId, $mysqli){
		$mysqli->query("DELETE FROM ".$this->getTableName()." WHERE OWNER_ID=".intval($ownerId)); 
	} 
}