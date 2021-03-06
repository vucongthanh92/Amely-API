<?php

/**
* 
*/
class ItemService extends Services
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
        $this->table = "amely_items";
    }

    public function renew($item_id, $duration)
    {
    	$item = $this->getItemByType($item_id);
    	$item = object_cast("Item", $item);
    	$item->data->stored_end = strtotime("+{$duration} days", $item->stored_end);
    	$item->where = "id = {$item->id}";
    	return $item->update();
    }

    public function getQuantityOfItemBySnapshot($snapshot_id, $owner_id, $type = 'user')
    {
    	$time = time();
    	$inventoryService = InventoryService::getInstance();
		$inventory = $inventoryService->getInventoryByType($owner_id, $type);
		if (!$inventory) return false;
		$item_params = null;
		$item_params[] = [
			'key' => 'owner_id',
			'value' => "= {$inventory->id}",
			'operation' => ''
		];
		$item_params[] = [
			'key' => 'snapshot_id',
			'value' => "= {$snapshot_id}",
			'operation' => 'AND'
		];
		$item_params[] = [
			'key' => 'quantity',
			'value' => "> 0",
			'operation' => 'AND'
		];
		$item_params[] = [
			'key' => 'status',
			'value' => "= 1",
			'operation' => 'AND'
		];
		$item_params[] = [
			'key' => '',
			'value' => "(stored_end >= {$time} AND stored_end <> 0)",
			'operation' => 'AND'
		];
		$item_params[] = [
			'key' => '',
			'value' => "(end_day >= {$time} OR end_day = 0)",
			'operation' => 'AND'
		];
		$item_params[] = [
			'key' => 'SUM(quantity) as sum',
			'value' => '',
			'operation' => 'query_params'
		];
		$item = $this->getItem($item_params);
		if (!$item) return 0;
		$quantity = $item->sum;
		return $quantity;
    }

    public function redeemQuantityBySnapshot($snapshot_id, $quantity, $owner_id, $type = 'user')
    {
    	$time = time();
    	$inventoryService = InventoryService::getInstance();
		$inventory = $inventoryService->getInventoryByType($owner_id, $type);
		if (!$inventory) return false;
		$item_params = null;
		$item_params[] = [
			'key' => 'owner_id',
			'value' => "= {$inventory->id}",
			'operation' => ''
		];
		$item_params[] = [
			'key' => 'snapshot_id',
			'value' => "= {$snapshot_id}",
			'operation' => 'AND'
		];
		$item_params[] = [
			'key' => 'quantity',
			'value' => "> 0",
			'operation' => 'AND'
		];
		$item_params[] = [
			'key' => 'status',
			'value' => "= 1",
			'operation' => 'AND'
		];
		$item_params[] = [
			'key' => '',
			'value' => "(stored_end >= {$time} AND stored_end <> 0)",
			'operation' => 'AND'
		];
		$item_params[] = [
			'key' => '',
			'value' => "(end_day >= {$time} OR end_day = 0)",
			'operation' => 'AND'
		];
		$item_params[] = [
			'key' => 'stored_end',
			'value' => "DESC",
			'operation' => 'order_by'
		];
		$items = $this->getItems($item_params, 0, 999999999999);
		if (!$items) return false;
		foreach ($items as $key => $item) {
			if ($item->quantity >= $quantity) {
				$item_id = $this->separateItem($item->id, $quantity);
				$this->updateStatus($item_id, 2);
				return true;
			} else {
				$quantity = $quantity - $item->quantity;
				$this->updateStatus($item->id, 2);
			}
		}
		return true;
    }

    public function updateStatus($item_id, $status)
    {
    	$item = new Item();
    	$item->data->status = $status;
		$item->where = "id = {$item_id}";
		return $item->update();
    }

    public function checkItemOfOwner($item_id, $owner_id, $type)
    {
    	$itemService = ItemService::getInstance();
    	$inventoryService = InventoryService::getInstance();
    	$item = $itemService->getItemByType($item_id, 'id');
    	if (!$item) return false;
    	if ($item->status != 1) return false;
		$inventory_params = null;
		$inventory_params[] = [
			'key' => 'id',
			'value' => "= {$item->owner_id}",
			'operation' => ''
		];
		$inventory = $inventoryService->getInventory($inventory_params);
		if ($inventory->owner_id == $owner_id && $inventory->type == $type) return $item;
		return false;
    }

    public function changeOwnerItem($owner_id, $type, $item_id)
    {
    	$inventoryService = InventoryService::getInstance();
    	$inventory = $inventoryService->getInventoryByType($owner_id, $type);
    	if (!$inventory) return false;
    	$item = new Item();
    	$item->data->time_created = time();
    	$item->data->owner_id = $inventory->id;
    	$item->data->givelist = 0;
    	$item->data->wishlist = 0;
    	$item->data->status = 1;
    	$item->where = "id = {$item_id}";
    	return $item->update();
    }

	public function save($data)
	{
		$productService = ProductService::getInstance();
		$product = $productService->getProductByType($data['product_id'], 'id');
		$time = time();

		$item = new Item();
		$item->data->owner_id = $data['inventory_id'];
		$item->data->type = 'inventory';
		$item->data->title = "";
		$item->data->description = "";
		$item->data->quantity = $data['quantity'];
		$item->data->snapshot_id = $data['snapshot_id'];
		$item->data->store_id = $data['store_id'];
		$item->data->price = $data['price'];
		$item->data->expiry_type = $product->expiry_type;
		$item->data->is_special = $product->is_special;
		$item->data->stored_end = strtotime("+{$product->storage_duration} days", $time);
		switch ((double)$product->expiry_type) {
		    case 0:
		        $item->data->end_day = 0;
		        break;
		    case 1:
		        $item->data->end_day = strtotime("+{$product->duration} days", $time);
		        break;
		    case 2:
		        $item->data->end_day = $product->end_day;
		        break;
		}
		$item->data->so_id = $data['so_id'];
		$item->data->wishlist = 0;
		$item->data->givelist = 0;
		$item->data->status = 1;
		return $item->insert();
	}

    public function separateItem($item_id, $quantity)
    {
		$item = $this->getItemByType($item_id, 'id');
		if (!$item) return false;
		if ($item->quantity == $quantity) return $item->id;
		if ($item->quantity < $quantity) return false;
		if ($item->quantity > $quantity) {
			$properties = get_object_vars($item);
    		$item = object_cast("Item", $item);
    		$item->data->quantity = $item->quantity - $quantity;
    		$item->where = "id = {$item_id}";
    		if ($item->update()) {
	    		$new_item = new Item();
				foreach ($properties as $key => $property) {
					$new_item->data->$key = $property;
				}
				$new_item->data->quantity = $quantity;
	    		return $new_item->insert(true);
    		}
		}
		return false;

    }

    public function getItemsByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "IN ({$input})",
			'operation' => ''
		];
		$items = $this->getItems($conditions, 0, 99999999);
		if (!$items) return false;
		return $items;
    }

    public function getItemByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$item = $this->getItem($conditions);
		if (!$item) return false;
		return $item;
    }

    public function getItem($conditions)
	{
		$item = $this->searchObject($conditions, 0, 1);
		if (!$item) return false;
		return $item;
	}

	public function getItems($conditions, $offset = 0, $limit = 10)
	{
		$items = $this->searchObject($conditions, $offset, $limit);
		if (!$items) return false;
		return array_values($items);
	}
}