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