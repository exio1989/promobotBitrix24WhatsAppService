<?php
class Logger{
    public $logFileName;
    public $type;
	
	function __construct($logFileName) {
	   $this->logFileName = $logFileName;
	}
	
	public function Info($s){
		$r=date("Y-m-d H:i:s")." [Info]: ".$s.PHP_EOL;
		echo $r;
		file_put_contents($this->logFileName,$r,FILE_APPEND);
	}
}

class EchoLogger{
    public $type;
	
	function __construct() {
	}
	
	public function Info($s){
		$r=date("Y-m-d H:i:s")." [Info]: ".$s.PHP_EOL;
		echo $r;
	}
}