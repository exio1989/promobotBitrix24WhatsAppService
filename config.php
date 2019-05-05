<?php
class Config {
	public $version = 27;
	
	public $webhook = "https://b24-linsdo.bitrix24.ru/rest/1/vxj13xj85t8l6cr8/";
	
	public $statDbHost = "localhost";
	public $statDbName = "hiravv2p_crmprom";
	public $statDbUser = "hiravv2p_crmprom";
	public $statDbPass = "WpYb6rrv";
	
	public $logPath = "log.txt";
	
	public static function logFilePath(){
		return __DIR__."/data/log.txt";
	}

	public static $logTimeStamp = "D M d 'y h.i A";
	
	public static $gaClientIdFieldName = "UF_CRM_1521051549";
	
	public $mailForAlert = "timergalin@gmail.com";
	
	public $alertMailSubject = "Произошла ошибка при экспорте лидов из битрикс24";
	
	public $whatsApiApiUri = "https://eu3.chat-api.com/instance39435/";
	public $whatsApiApiToken = "lxk5u0j7dlxxl77o";
}
?>