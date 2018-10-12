<?php
namespace Amely\Shipping\SQ;

class Storage extends \Object
{

	public $creator_id;
	public $so_id;
	function __construct()
	{
		parent::__construct();
	}
	
	public function process()
	{	
		$inventoryService = \InventoryService::getInstance();
		$inventory_params = null;
		$inventory_params[] = [
			'key' => 'type',
			'value' => "= 'user'",
			'operation' => ''
		];
		$inventory_params[] = [
			'key' => 'owner_id',
			'value' => "= {$creator_id}",
			'operation' => 'AND'
		];
		$inventory = $inventoryService->getInventory($inventory_params);
		if (!$inventory) return false;

		die('1231456');
	}

	public function checkFee()
	{
		
	}

	public function redeemDelivery()
    {
    }
}
