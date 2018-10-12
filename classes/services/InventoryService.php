<?php

/**
* 
*/
class InventoryService extends Services
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
        $this->table = "amely_inventories";
    }

    public function getInventoryByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$inventory = $this->getInventory($conditions);
		if (!$inventory) return false;
		return $inventory;
    }

    public function getInventory($conditions)
	{
		$inventory = $this->searchObject($conditions, 0, 1);
		if (!$inventory) return false;
		return $inventory;
	}

	public function getInventories($conditions, $offset = 0, $limit = 10)
	{
		$inventories = $this->searchObject($conditions, $offset, $limit);
		if (!$inventories) return false;
		return array_values($inventories);
	}
}