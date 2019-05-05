<?php
require_once "class.entity-storage.php";

class CrmProductsStorage extends CrmEntityStorage{	
	protected function getTableName(){
		return "crm_products";
	}
}