<?php
namespace Amely\Shipping\SQ;

class Storage extends \Object
{

	public $item;
	public $creator_id;
	public $so_id;

	function __construct()
	{
		parent::__construct();
	}
	
	public function process()
	{	
		$item = $this->item;
		$creator_id = $this->creator_id;
		$so_id = $this->so_id;

		$inventoryService = \InventoryService::getInstance();
		$itemService = \ItemService::getInstance();
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
		if ($inventory) {
			$inventory_id = $inventory->id;
		} else {
			$data = null;
			$data['creator_id'] = $creator_id;
			$data['owner_id'] = $creator_id;
			$data['type'] = 'user';
			$inventory_id = $inventoryService->save($data);
		}
		$data = [];
		$data['product_id'] = $item['product_id'];
		$data['inventory_id'] = $inventory_id;
		$data['quantity'] = $item['quantity'];
		$data['snapshot_id'] = $item['snapshot_id'];
		$data['store_id'] = $item['store_id'];
		$data['price'] = $item['price'];
		$data['so_id'] = $so_id;
		return $itemService->save($data);
	}

	public function checkFee()
	{
		
	}

	public function redeemDelivery()
    {
    }
}
