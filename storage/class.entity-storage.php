<?php
//MySql types
define("BIT",16);
define("TINYINT",1);
define("SMALLINT",2);
define("MEDIUMINT",9);
define("INTEGER",3);
define("BIGINT",8);

define("FLOAT",4);
define("DOUBLE",5);
define("DECIMAL",246);

define("DATE",10);
define("DATETIME", 12);

define("TIMESTAMP",7);
define("TIME",11);
define("YEAR",13);

define("CHAR",254);
define("VARCHAR",253);
define("TEXT",252);

class MySqlField{
    public $name;
    public $type;
	
	function __construct($name,$type) {
	   $this->type = $type;
       $this->name = $name;
	}
}

abstract class CrmEntityStorage{
	protected $logger;
	protected $dbHost;
	protected $dbName;
	protected $dbUser;
	protected $dbPass;
	
	public function __construct($config,$logger)
	{
	   $this->logger = $logger;
	   $this->dbHost = $config->statDbHost;
       $this->dbName = $config->statDbName;
	   $this->dbUser = $config->statDbUser;
	   $this->dbPass = $config->statDbPass;
	}
	
	abstract protected function getTableName();
	
	public function deleteAllItems(){
		$mysqli = $this->openConnection();
		$this->deleteAllItemsInternal($mysqli); 
		$mysqli->close();		
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
	
	public function addItem($item){
		$mysqli = $this->openConnection();
		$items = array();
		$items[] = $item;
		$this->addItemsInternal($items,$mysqli);
		$mysqli->close();
	}
	
	public function addItems($items){
		$mysqli = $this->openConnection();
		$this->addItemsInternal($items,$mysqli);
		$mysqli->close();
	}
	
	protected function deleteAllItemsInternal($mysqli){
		$mysqli->query("TRUNCATE TABLE ".$this->getTableName()); 
	}
	
	protected function updateItemInternal($item,$mysqli){
		$this->deleteItemInternal($item["ID"],$mysqli);
		$this->addItemsInternal(array($item),$mysqli);
	}
	
	protected function deleteItemInternal($itemId,$mysqli){
		$mysqli->query("DELETE FROM ".$this->getTableName()." WHERE ID=".intval($itemId));
	}
	
	protected function addItemsInternal($items,$mysqli){	
		if($items==FALSE || count($items)==0){
			return;
		}
		$fields = $this->getFields($mysqli);
		  
		$itemsChunks = array_chunk($items,100);  
		foreach($itemsChunks as $itemsChunk){
			$insertIds = array();
			foreach($itemsChunk as $item){
				if($item["ID"] && $item["ID"]!=""){
					$insertIds[]=$item["ID"];
				}
			} 
			if(count($insertIds)>0){
				$sqlDelete = "DELETE FROM ".$this->getTableName()." WHERE ID IN (".implode(",",$insertIds).")";
				if (!$mysqli->query($sqlDelete)){
					$this->logger->error($sqlDelete);
					$this->logger->error("Не удалось выполнить запрос: ".$mysqli->error);
					exit();
				}			
			}
			
			$insertValues = array();
			foreach($itemsChunk as $item){
				$insertValues[]="(".$this->createValuesString($item,$fields,$mysqli).")";
			} 
			$sqlInsert = "INSERT INTO ".$this->getTableName().PHP_EOL
					." (".$this->createNamesString($itemsChunk[0],$fields).")".PHP_EOL
					." VALUES ".implode(",".PHP_EOL,$insertValues).PHP_EOL;
			if (!$mysqli->query($sqlInsert)){
				$this->logger->error($sqlInsert);
				$this->logger->error("Не удалось выполнить запрос: ".$mysqli->error);
				exit();
			} 
			
		}
	}   
	
	protected function toUtcDate($date){
		return gmdate('Y-m-d H:i:s', strtotime($date));
	}
	
	protected function toDatetimeOffset($date){
		return date('Y-m-d\TH:i:s+00:00', strtotime($date));
	}
	
	protected function isDate($fieldName,$fields){
		return $this->isType($fieldName,DATE,$fields)
			|| $this->isType($fieldName,DATETIME,$fields);     
	}
	
	protected function isInteger($fieldName,$fields){
		return $this->isType($fieldName,BIT,$fields)
			|| $this->isType($fieldName,TINYINT,$fields)
			|| $this->isType($fieldName,SMALLINT,$fields)
			|| $this->isType($fieldName,MEDIUMINT,$fields)
			|| $this->isType($fieldName,INTEGER,$fields)
			|| $this->isType($fieldName,BIGINT,$fields);     
	}
	
	protected function isDouble($fieldName,$fields){
		return $this->isType($fieldName,FLOAT,$fields)
			|| $this->isType($fieldName,DOUBLE,$fields)
			|| $this->isType($fieldName,DECIMAL,$fields);     
	}
	
	protected function isType($fieldName,$type,$fields){		
		foreach($fields as $field){
			if($field->name == $fieldName){
				return $field->type == $type;
			}
		}
		return false;     
	}
	
	protected function containsField($fieldName,$fields){
		foreach($fields as $field){
			if($fieldName==$field->name){
				return TRUE;
			}
		}
		return FALSE;
	}
	
	protected function createValuesString($inValues,$fields,$mysqli){	
		$values = array();
		foreach($inValues as $k => $v){
			if(!$this->containsField($k,$fields)){
				continue;
			}
					
			switch(gettype($v)){
				case "integer":
					$values[] = $v;
					break;
				case "double": 
					$values[] = $v;
					break;
				case "string":
					if($v==""){
						$values[] = "NULL";
					}else{
						if($this->isDate($k,$fields)){	
							$values[] = "'".$this->toUtcDate($v)."'";
						}else
						if($this->isInteger($k,$fields)){	
							if(is_numeric($v)){
								$values[] = $v;								
							}else{
								$values[] = "NULL";
							}
						}else
						if($this->isDouble($k,$fields)){	
							if(is_numeric($v)){
								$values[] = $v;
							}else{
								$values[] = "NULL";
							}
						}					
						else{							
							$values[] = "'".$mysqli->real_escape_string($v)."'";
							//$values[] = "'".addslashes(htmlspecialchars($v))."'";
						}
					}
					break;
				case "NULL":
					$values[] = "NULL";
					break;
				case "boolean":
					$values[] = $v?1:0;
					break;
				default:
					$this->logger->error("FAIL ".$k." - ".gettype($v));
					break;
			}
		}
		return implode(',',$values);
	}
	
	protected function createNamesString($values,$fields){
		$names = array();
		foreach($values as $k => $v){
			if(!$this->containsField($k,$fields)){
				continue;
			}
						
			$names[] = $k;
		}
		return implode(',',$names);
	}
	
	protected function openConnection(){
		$mysqli = new mysqli($this->dbHost, $this->dbUser, $this->dbPass, $this->dbName);	
		$mysqli->set_charset("utf8");
		if ($mysqli->connect_errno) {
			$this->logger->error("Не удалось подключиться: %".($mysqli->connect_error));
			exit();
		}
		
		return $mysqli;
	}
		
	protected function getFields($mysqli){
		$fields = array();
		
		$query = "SELECT * from " . $this->getTableName()." LIMIT 1";

		if($result = $mysqli->query($query)){
			while ($column_info = $result->fetch_field()){
				$fields[] = new MySqlField($column_info->name,$column_info->type);
			}
		}

		return $fields;
	}
}