<?php

/**
* 
*/
class BusinessService extends Services
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
        $this->table = "amely_business_pages";
    }

  //   public function getStoreById($id, $getAddr = true)
  //   {
  //   	$conditions = null;
		// $conditions[] = [
		// 	'key' => 'id',
		// 	'value' => "= '{$input}'",
		// 	'operation' => ''
		// ];
		// $store = $this->getStore($conditions, $getAddr);
		// if (!$store) return false;
		// return $store;
  //   }

    public function getBusiness($conditions, $getAddr = true)
	{
		$addressService = AddressService::getInstance();
		$store = $this->searchObject($conditions, 0, 1);
		if (!$store) return false;
		$store = $this->changeStructureInfo($store, $getAddr);
		return $store;
	}

	public function getStores($conditions, $offset = 0, $limit = 10, $getAddr = true)
	{
		$addressService = AddressService::getInstance();
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
		if ($page->avatar) {
			$page->avatar = $page->photoURL("larger");
		}
		if ($page->cover){
			$page->cover = $page->coverURL();
		}

		return $store;
	}
}