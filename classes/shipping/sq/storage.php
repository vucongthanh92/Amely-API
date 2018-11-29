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
		$so_id = $this->so_id;
		$inventoryService = \InventoryService::getInstance();
		$supplyOrderService = \SupplyOrderService::getInstance();
		$purchaseOrderService = \PurchaseOrderService::getInstance();
		$userService = \UserService::getInstance();
		$notificationService = \NotificationService::getInstance();
		$storeService = \StoreService::getInstance();

		$so = $supplyOrderService->getSOByType($so_id, 'id');
		$po = $purchaseOrderService->getPOByType($so->owner_id, 'id');
		
		$itemService = \ItemService::getInstance();
		$inventory_params = null;
		$inventory_params[] = [
			'key' => 'type',
			'value' => "= 'user'",
			'operation' => ''
		];
		$inventory_params[] = [
			'key' => 'owner_id',
			'value' => "= {$po->owner_id}",
			'operation' => 'AND'
		];
		$inventory = $inventoryService->getInventory($inventory_params);
		if ($inventory) {
			$inventory_id = $inventory->id;
		} else {
			$inventory_data = null;
			$inventory_data['creator_id'] = $po->owner_id;
			$inventory_data['owner_id'] = $po->owner_id;
			$inventory_data['type'] = 'user';
			$inventory_id = $inventoryService->save($inventory_data);
		}

		$order_items = unserialize($so->order_items_snapshot);
		if (!$order_items) return false;
		$items_sos = [];
		foreach ($order_items as $key => $item) {

			$item_data = null;
			$item_data['product_id'] = $item['product_id'];
			$item_data['inventory_id'] = $inventory_id;
			$item_data['quantity'] = $item['quantity'];
			$item_data['snapshot_id'] = $item['snapshot_id'];
			$item_data['store_id'] = $item['store_id'];
			$item_data['price'] = $item['price'];
			$item_data['so_id'] = $so_id;
			$itemService->save($item_data);
		}

		return true;
	}

	public function checkFee()
	{
		return false;
	}

	public function redeemDelivery()
    {
    }
}
