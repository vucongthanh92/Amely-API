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
    public function checkItemOfOwner($item_id, $owner_id, $type)
    {
    	$itemService = ItemService::getInstance();
    	$inventoryService = InventoryService::getInstance();
    	$item = $itemService->getItemByType($item_id, 'id');
    	if ($item->status == 1) {
    		$inventory_params = null;
    		$inventory_params[] = [
    			'key' => 'id',
    			'value' => "= {$item->id}",
    			'operation' => ''
    		];
    		$inventory = $inventoryService->getInventory($inventory_params);
    		if ($inventory->owner_id == $owner_id && $inventory->type == $type) return true;
    		return false;
    	}
    }

    public function changeOwnerItem($owner_id, $type, $data)
    {
    	$inventoryService = InventoryService::getInstance();
    	$inventory = $inventoryService->getInventoryByType($owner_id, $type);
    	if (!$inventory) return false;
    	$item = new Item();
    	foreach ($data as $key => $value) {
    		if ($key == 'id') {
    			$item->where = "id = {$value}";
    		}
    		$item->data->time_created = time();
    		$item->data->givelist = 0;
    		$item->data->wishlist = 0;
    		$item->data->$key = $value;
    	}
    	$item->data->owner_id = $inventory->id;
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