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

    public function save($owner_id, $type, $creator_id)
    {
    	$inventory = new Inventory();
    	$inventory->data->owner_id = $owner_id;
		$inventory->data->type = $type;
		$inventory->data->creator_id = $creator_id;
		$inventory->data->salt = "";
		$inventory->data->password = "";
		return $inventory->insert(true);
    }

    public function getInventoryByType($input, $type ='user')
    {
    	$conditions = null;
    	$conditions[] = [
			'key' => 'owner_id',
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$conditions[] = [
			'key' => 'type',
			'value' => "= '{$type}'",
			'operation' => 'AND'
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