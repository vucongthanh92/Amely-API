<?php

/**
* 
*/
class PurchaseOrderService extends Services
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
        $this->table = "amely_purchase_order";
    }

    public function save($data)
    {
    	$po = new PurchaseOrder();
    	foreach ($data as $key => $value) {
    		$po->data->$key = $value;
    	}
    	if ($data['payment_method'] == "quickpay/cos") {
    		$po->data->shipping_method = "sq/storage";
    	}
    	return $po->insert(true);
    }

    public function updateStatus($po_id, $status)
    {
    	$po = new PurchaseOrder();
    	$po->data->status = $status;
    	$po->data->id = $po_id;
    	$po->where = "id = {$po_id}";
    	return $po->update(true);
    }

    public function getPOByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$po = $this->getPO($conditions);
		if (!$po) return false;
		return $po;
    }

    public function getPO($conditions)
	{
		$po = $this->searchObject($conditions, 0, 1);
		if (!$po) return false;
		$po = $this->changeStructureInfo($po);
		return $po;
	}

	public function getPOs($conditions, $offset = 0, $limit = 10)
	{
		$pos = $this->searchObject($conditions, $offset, $limit);
		if (!$pos) return false;
		foreach ($pos as $key => $po) {
			$po = $this->changeStructureInfo($po);
			$pos[$key] = $po;
		}
		return array_values($pos);
	}

	private function changeStructureInfo($po)
	{
		if (!$po->shipping_fee) {
			$po->shipping_fee = 0;
		}
		$addressService = AddressService::getInstance();
		$po->display_order = convertPrefixOrder("HD", $po->id, $po->time_created);
		if ($po->payment_province && $po->payment_district && $po->payment_ward) {
			$po_payment_province = $addressService->getAddress($po->payment_province, 'province');
			$po_payment_district = $addressService->getAddress($po->payment_district, 'district');
			$po_payment_ward = $addressService->getAddress($po->payment_ward, 'ward');

		    $po->payment_province_name = $po_payment_province->name;
		    $po->payment_district_name = $po_payment_district->name;
		    $po->payment_ward_name = $po_payment_ward->name;

		    $po_payment_province = $po_payment_province->type .' '. $po_payment_province->name;
		    $po_payment_district = $po_payment_district->type .' '. $po_payment_district->name;
		    $po_payment_ward = $po_payment_ward->type .' '. $po_payment_ward->name;

		    $po->payment_full_address = $po->payment_address.', '.$po_payment_ward.', '.$po_payment_district.', '.$po_payment_province;
		}

		if ($po->shipping_province && $po->shipping_district && $po->shipping_ward) {
			$po_shipping_province = $addressService->getAddress($po->shipping_province, 'province');
			$po_shipping_district = $addressService->getAddress($po->shipping_district, 'district');
			$po_shipping_ward = $addressService->getAddress($po->shipping_ward, 'ward');

		    $po->shipping_province_name = $po_shipping_province->name;
		    $po->shipping_district_name = $po_shipping_district->name;
		    $po->shipping_ward_name = $po_shipping_ward->name;

		    $po_shipping_province = $po_shipping_province->type .' '. $po_shipping_province->name;
		    $po_shipping_district = $po_shipping_district->type .' '. $po_shipping_district->name;
		    $po_shipping_ward = $po_shipping_ward->type .' '. $po_shipping_ward->name;

		    $po->shipping_full_address = $po->shipping_address.', '.$po_shipping_ward.', '.$po_shipping_district.', '.$po_shipping_province;
		}

		return $po;
	}

}