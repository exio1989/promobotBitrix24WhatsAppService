<?php
	require 'config.php';
	require_once 'class.logger.php';
	require_once 'storage/class.currencies-storage.php';
	
	$config = new Config();
    $logger = new Logger($config->logPath);
	$storage = new CrmCurrenciesStorage($config,$logger);
	echo $storage->getCurrencyValue($_GET['currency']);