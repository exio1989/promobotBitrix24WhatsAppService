<?php
require_once "class.entity-storage.php";

class CrmUsersStorage extends CrmEntityStorage{	
	protected function getTableName(){
		return "crm_users";
	}
}