<?php

/**
* 
*/
class ProductStoreService extends Services
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
        $this->table = "amely_product_store";
    }

    public function checkQuantityInStore($product_id, $store_id)
    {
    	$conditions = null;
    	$conditions[] = [
    		'key' => 'product_id',
    		'value' => "= '{$product_id}'",
    		'operation' => ''
    	];
    	$conditions[] = [
    		'key' => 'store_id',
    		'value' => "= '{$store_id}'",
    		'operation' => 'AND'
    	];
    	$store_quantity = $this->getQuantityProduct($conditions);
		if (!$store_quantity) return false;
		return $store_quantity;
    }

    public function getQuantityByType($input, $type = 'id', $offset = 0, $limit = 10)
    {
    	$conditions = null;
    	$conditions[] = [
    		'key' => $type,
    		'value' => "= '{$input}'",
    		'operation' => ''
    	];
    	$store_quantity = $this->getQuantityProducts($conditions, $offset, $limit);
		if (!$store_quantity) return false;
		return $store_quantity;
    }

    public function getQuantityProduct($conditions)
	{
		$product = $this->searchObject($conditions, 0, 1);
		if (!$product) return false;
		return $product;
	}

	public function getQuantityProducts($conditions, $offset = 0, $limit = 10)
	{
		$products = $this->searchObject($conditions, $offset, $limit);
		if (!$products) return false;
		return array_values($products);
	}
}