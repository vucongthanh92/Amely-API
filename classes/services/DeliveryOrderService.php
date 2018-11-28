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
		$do->display_order = convertPrefixOrder("GH", $do->id, $do->time_created);

		$do_shipping_province = $addressService->getAddress($do->shipping_province, 'province');
		$do_shipping_district = $addressService->getAddress($do->shipping_district, 'district');
		$do_shipping_ward = $addressService->getAddress($do->shipping_ward, 'ward');

	    $do->shipping_province_name = $do_shipping_province->name;
	    $do->shipping_district_name = $do_shipping_district->name;
	    $do->shipping_ward_name = $do_shipping_ward->name;
		return $do;
	}

}