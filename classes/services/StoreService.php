<?php

/**
* 
*/
class StoreService extends Services
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
        $this->table = "amely_stores";
    }

    public function save($data)
    {
    	$services = Services::getInstance();

    	$store = new Store();
    	foreach ($data as $key => $value) {
    		$store->data->$key = $value;
    	}
    	$store->data->approved = 0;
    	$store->data->type = 'shop';
    	if ($data['id']) {
    		$store->where = "id = {$data['id']}";
    		if ($store->update(true)) {
    			$s = $this->getStoreByType($data['id'], 'id');
    			$services->elasticsearch($s, 'shop', 'update');
    			return true;
    		}
    	} else {
    		return $store->insert(true);
    	}
    	return false;
    }

    public function approval($store_id)
    {
    	$services = Services::getInstance();

    	$s = $this->getStoreByType($store_id, 'id');

    	$store = new Store();
    	$store->data->approved = time();
    	$store->data->status = 1;
    	$store->data->id = $store_id;
    	$store->where = "id = {$store_id}";
    	if ($store->update(true)) {
    		$services->elasticsearch($s, 'shop', 'insert');
    		return true;
    	}
    	return false;
    }

    public function delete($store_id)
    {
    	$services = Services::getInstance();
    	$productStoreService = ProductStoreService::getInstance();

    	$deleted = true;
    	$s = $this->getStoreByType($store_id, 'id');
    	$ps = $productStoreService->getQuantityByType($store_id, 'store_id', 0, 9999999999);
    	if ($ps) {
	    	foreach ($ps as $key => $value) {
	    		if ($value->quantity > 0) {
	    			$deleted = false;
	    			break;
	    		}
	    	}
    	}
    	if ($deleted) {
    		$services->elasticsearch($s, 'shop', 'delete');
    		$store = new Store();
	    	$store->where = "id = {$store_id}";
	    	return $store->delete();
    	}
    	return false;
    }

    public function updateStatus($store_id, $status)
    {
    	$s = $this->getStoreByType($store_id, 'id');
    	switch ($status) {
    		case 0:
    			$services->elasticsearch($s, 'shop', 'delete');
    			break;
    		case 1:
    			$services->elasticsearch($s, 'shop', 'insert');
    			break;
    		default:
    			break;
    	}
    	$store = new Store();
    	$store->data->status = $status;
    	$store->data->id = $store_id;
    	$store->where = "id = {$store_id}";
    	return $store->update(true);	
    }

    public function getStoresByShop($shop_id, $getAddr = true)
    {
    	$conditions = null;
		$conditions[] = [
			'key' => 'owner_id',
			'value' => "= {$shop_id}",
			'operation' => ''
		];
		$stores = $this->getStores($conditions, 0, 99999999, $getAddr);
		if (!$stores) return false;
		return $stores;
    }

    public function getStoresByType($input, $type ='id', $getAddr = true)
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "IN ({$input})",
			'operation' => ''
		];
		$stores = $this->getStores($conditions, 0, 99999999, $getAddr);
		if (!$stores) return false;
		return $stores;
    }

    public function getStoreByType($input, $type ='id', $getAddr = true)
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$store = $this->getStore($conditions, $getAddr);
		if (!$store) return false;
		return $store;
    }

    public function getStore($conditions, $getAddr = true)
	{
		$store = $this->searchObject($conditions, 0, 1);
		if (!$store) return false;
		$store = $this->changeStructureInfo($store, $getAddr);
		return $store;
	}

	public function getStores($conditions, $offset = 0, $limit = 10, $getAddr = true)
	{
		$stores = $this->searchObject($conditions, $offset, $limit);
		if (!$stores) return false;
		foreach ($stores as $key => $store) {
			$store = $this->changeStructureInfo($store, $getAddr);
			$stores[$key] = $store;
		}
		if (!$stores) return false;
		return array_values($stores);
	}

	private function changeStructureInfo($store, $getAddr = true)
	{
		$addressService = AddressService::getInstance();
		if ($getAddr) {
			if ($store->store_province && $store->store_district && $store->store_ward) {
				$store_province = $addressService->getAddress($store->store_province, 'province');
				$store_district = $addressService->getAddress($store->store_district, 'district');
				$store_ward = $addressService->getAddress($store->store_ward, 'ward');
				$store->store_province_name = $store_province->name;
				$store->store_district_name = $store_district->name;
				$store->store_ward_name = $store_ward->name;
			    $store_province = $store_province->type .' '. $store_province->name;
			    $store_district = $store_district->type .' '. $store_district->name;
			    $store_ward = $store_ward->type .' '. $store_ward->name;
			    $store->full_address = $store->store_address.' '.$store_ward.' '.$store_district.' '.$store_province;
			}
		}

		return $store;
	}
}