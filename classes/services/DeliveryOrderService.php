<?php

/**
* 
*/
class DeliveryOrderService extends Services
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
        $this->table = "amely_delivery_order";
    }

    public function save($data)
    {
    	$do = new DeliveryOrder();
    	foreach ($data as $key => $value) {
    		$do->data->$key = $value;
    	}
    	return $do->insert(true);
    }

    public function getDOsBySO($so_id, $offset = 0, $limit = 10)
    {    	
    	$conditions = null;
		$conditions[] = [
			'key' => 'so_id',
			'value' => "= '{$so_id}'",
			'operation' => ''
		];
		$dos = $this->getDOs($conditions, $offset, $limit);
		if (!$dos) return false;
		return $dos;
    }

    public function getDOsByStore($store_id, $offset = 0, $limit = 10)
    {    	
    	$conditions = null;
		$conditions[] = [
			'key' => 'store_id',
			'value' => "= '{$store_id}'",
			'operation' => ''
		];
		$dos = $this->getDOs($conditions, $offset, $limit);
		if (!$dos) return false;
		return $dos;
    }

    public function getDOsByUser($owner_id, $offset = 0, $limit = 10)
    {    	
    	$conditions = null;
		$conditions[] = [
			'key' => 'owner_id',
			'value' => "= '{$owner_id}'",
			'operation' => ''
		];
		$dos = $this->getDOs($conditions, $offset, $limit);
		if (!$dos) return false;
		return $dos;
    }

    public function getDOByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$do = $this->getDO($conditions);
		if (!$do) return false;
		return $do;
    }

    public function getDO($conditions)
	{
		$do = $this->searchObject($conditions, 0, 1);
		if (!$do) return false;
		$do = $this->changeStructureInfo($do);
		return $do;
	}

	public function getDOs($conditions, $offset = 0, $limit = 10)
	{
		$dos = $this->searchObject($conditions, $offset, $limit);
		if (!$dos) return false;
		foreach ($dos as $key => $do) {
			$do = $this->changeStructureInfo($do);
			$dos[$key] = $do;
		}
		return array_values($dos);
	}

	private function changeStructureInfo($do)
	{
		$addressService = AddressService::getInstance();
		$do->display_order = convertPrefixOrder("GH", $do->id, $do->time_created);

		$do_shipping_province = $addressService->getAddress($do->shipping_province, 'province');
		$do_shipping_district = $addressService->getAddress($do->shipping_district, 'district');
		$do_shipping_ward = $addressService->getAddress($do->shipping_ward, 'ward');

	    $do->shipping_province_name = $do_shipping_province->name;
	    $do->shipping_district_name = $do_shipping_district->name;
	    $do->shipping_ward_name = $do_shipping_ward->name;

	    $do_shipping_province = $do_shipping_province->type .' '. $do_shipping_province->name;
	    $do_shipping_district = $do_shipping_district->type .' '. $do_shipping_district->name;
	    $do_shipping_ward = $do_shipping_ward->type .' '. $do_shipping_ward->name;

	    $do->shipping_full_address = $do->shipping_address.', '.$do_shipping_ward.', '.$do_shipping_district.', '.$do_shipping_province;

		return $do;
	}

}