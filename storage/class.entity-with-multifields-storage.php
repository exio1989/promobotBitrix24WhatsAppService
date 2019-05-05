<?php
require_once "class.entity-storage.php";
require_once "class.multifield-storage.php";

abstract class CrmEntityWithMultifieldStorage extends CrmEntityStorage{
	private $multifieldsStorages = array();
	
	function __construct($multifieldPairs, $config,$logger) {
	   parent::__construct($config,$logger);
	   foreach($multifieldPairs as $fieldName => $tableName){
			$this->multifieldsStorages[$fieldName] = new CrmMultifieldStorage($tableName,$config,$logger);
	   }
	}
	
	protected function ewmAddItem($item,$mysqli){
		$items = array();
		$items[] = $item;
		$this->addItemsInternal($items,$mysqli);
		$this->replaceMultifields($item,$mysqli);
	}
	
	protected function ewmAddItems($items,$mysqli){
		$this->addItemsInternal($items,$mysqli);
		foreach($items as $item){
			$this->replaceMultifields($item,$mysqli);
		}
	}
	
	protected function ewmUpdateItem($item,$mysqli){
		$this->updateItemInternal($item,$mysqli);
		$this->replaceMultifields($item,$mysqli);	
	}
	
	protected function ewmDeleteItem($itemId,$mysqli){
		$this->deleteItemInternal($itemId,$mysqli);
		$this->deleteMultifields($itemId,$mysqli);
	}
	
	protected function ewmDeleteAllItems($mysqli){
		$this->deleteAllItemsInternal($mysqli);
		foreach($this->multifieldsStorages as $fieldName => $storage){
			$storage->deleteAllItemsInternal($mysqli);
	    }	
	}
	
	private function replaceMultifields($item,$mysqli){
		foreach($this->multifieldsStorages as $fieldName => $storage){
			if(isset($item[$fieldName])){
				$storage->replaceItemsByOwnerId($item['ID'],$item[$fieldName],$mysqli);
			}
	    }
	}
	
	private function deleteMultifields($itemId,$mysqli){
		foreach($this->multifieldsStorages as $fieldName => $storage){
			$storage->deleteItemsByOwnerId($itemId,$mysqli);
	    }
	}
}