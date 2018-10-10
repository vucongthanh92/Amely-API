<?php

/**
* 
*/
class PurchaseOrderService extends Services
{
	protected static $instance = null;

	public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance =  new self();
		}
		return self::$instance;
	}

	public function __construct() 
	{
        $this->table = "amely_purchase_order";
    }

    public function getPOByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$po = $this->getPO($conditions);
		if (!$po) return false;
		return $po;
    }

    public function getPO($conditions)
	{
		$po = $this->searchObject($conditions, 0, 1);
		if (!$po) return false;
		return $po;
	}

	public function getPOs($conditions, $offset = 0, $limit = 10)
	{
		$pos = $this->searchObject($conditions, $offset, $limit);
		if (!$pos) return false;
		return array_values($pos);
	}

}