<?php
require_once 'bitrix24-php-sdk/vendor/autoload.php';

class CurrencyEntity
{
	private $client;
	
	public function __construct($obB24App)
	{
		$this->client = $obB24App;
	}

	public function get($currencyId)
	{
		$fullResult = $this->client->call(
			'crm.currency.get',
			array('id' => $currencyId)
		);
		return $fullResult;
	}

	public function getList($order = array(), $filter = array(), $select = array(), $start = 0)
	{
		$fullResult = $this->client->call(
			'crm.currency.list',
			array(
				'order' => $order,
				'filter'=> $filter,
				'select'=> $select,
				'start'	=> $start
			)
		);
		return $fullResult;
	}
	
	
	public function getListQuery($order = array(), $filter = array(), $select = array(), $start = 0)
    {
        return array(
            'method' => 'crm.currency.list',
            'parameters' => array(
                'order' => $order,
                'filter' => $filter,
                'select' => $select,
                'start' => $start
            )
        );
    }
}
