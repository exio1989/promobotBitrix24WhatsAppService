<?php
 require 'config.php';
 require_once 'class.psr-logger.php';
 require_once 'class.bitrix24-exporter.php';

try {
	$config = new Config();
    $logger = new PsrLogger(Config::logFilePath(),$config->mailForAlert,$config->alertMailSubject);
	$id = rand();
	$logger->info("Start export ".$id);
	$exporter = new CrmExporter($config,$logger);
	$exporter->exportAll();	
	$logger->info("Finish export ".$id);
} catch (Exception $e) {
	$logger->error($e->getMessage().PHP_EOL.$e->getTrace());
}
?>