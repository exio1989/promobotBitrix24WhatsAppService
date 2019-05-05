<?php
require_once 'bitrix24-php-sdk/vendor/autoload.php';
 
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
 
 class PsrLogger extends AbstractLogger
{
    public $logFileName;
	private $adminMail;
	private $mailSubject;
	
	function __construct($logFileName, $adminMail, $mailSubject) {
	   $this->logFileName = $logFileName;
	   $this->adminMail = $adminMail;
	   $this->mailSubject = $mailSubject;
	}
	
	
    public function log($level, $message, array $context = array())
    {
		switch($level){
			case LogLevel::EMERGENCY:
				$resultMessage = $this->createTraceMessage($message);
				$this->sendAlert($resultMessage);
				break;
			case LogLevel::ALERT:
				$resultMessage = $this->createTraceMessage($message);
				$this->sendAlert($resultMessage);
				break;
			case LogLevel::CRITICAL:
				$resultMessage = $this->createTraceMessage($message);
				$this->sendAlert($resultMessage);
				break;
			case LogLevel::ERROR:
				$resultMessage = $this->createTraceMessage($message);
				$this->sendAlert($resultMessage);
				break;
			case LogLevel::WARNING:
				$resultMessage = $this->createTraceMessage($message);
				$this->sendAlert($resultMessage);
				break;
			case LogLevel::NOTICE:
				$resultMessage = $message;
				break;
			case LogLevel::INFO:
				$resultMessage = $message;
				break;
			case LogLevel::DEBUG:
				$resultMessage = $message;
				break;
			default:			
				$resultMessage = $message;
				break;
		}
       file_put_contents($this->logFileName,date("Y-m-d H:i:s")."[".$level."]: ".$resultMessage.PHP_EOL.PHP_EOL,FILE_APPEND);
    }
	
	private function sendAlert($message){
		mail($this->adminMail,$this->mailSubject,str_replace(PHP_EOL,"<br>".PHP_EOL,$message));
	}
	
	private function createTraceMessage($msg){
		$traceRows = debug_backtrace();
		array_shift($traceRows);
		array_shift($traceRows);
		$traceString="";
		foreach($traceRows as $traceRow){
			$traceString.= 
			"file: ".$traceRow["file"].PHP_EOL
			."   ->class: ".$traceRow["class"].PHP_EOL
			."   ->function: ".$traceRow["function"].PHP_EOL
			."   ->line: ".$traceRow["line"].PHP_EOL;
		}
		
		return $msg.PHP_EOL."Stack trace:".PHP_EOL.$traceString;
	}
}