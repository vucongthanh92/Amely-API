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

    public function saveItem($owner_id, $type, $order_item_snapshot, $so_id)
	{
		$productDetailService = ProductDetailService::getInstance();
		$time = time();
		$pdetail = $productDetailService->getDetailProductByType($order_item_snapshot['pdetail_id'], 'id');

		$item = new Item();
		$item->data->owner_id = $owner_id;
		$item->data->type = $type;
		$item->data->title = "";
		$item->data->description = "";
		$item->data->quantity = $order_item_snapshot['quantity'];
		$item->data->snapshot = $order_item_snapshot['snapshot_id'];
		$item->data->store_id = $order_item_snapshot['store_id'];
		$item->data->price = $order_item_snapshot['price'];
		$item->data->expiry_type = $pdetail->expiry_type;
		$item->data->is_special = $pdetail->is_special;
		$item->data->stored_end = strtotime("+{$pdetail->storage_duration} days", $time);
		switch ((double)$pdetail->expiry_type) {
		    case 0:
		        $item->data->end_day = 0;
		        break;
		    case 1:
		        $item->data->end_day = strtotime("+{$pdetail->duration} days", $time);
		        break;
		    case 2:
		        $item->data->end_day = $pdetail->end_day;
		        break;
		}
		$item->data->so_id = $so_id;
		$item->data->wishlist = 0;
		$item->data->givelist = 0;
		$item->data->status = 1;
		return $item->insert();
	}


    public function separateItem($item_id)
    {
    	$conditions = null;
		$conditions[] = [
			'key' => 'id',
			'value' => "= '{$item_id}'",
			'operation' => ''
		];
		$item = $this->getItem($item);
		if (!$item) return false;

    	$item = object_cast("Item", $item);
    	unset($item->id);
    	return $item->insert(true);

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