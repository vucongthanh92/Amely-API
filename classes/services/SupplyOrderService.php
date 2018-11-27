<?php

/**
* 
*/
class SupplyOrderService extends Services
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
        $this->table = "amely_supply_order";
    }

    public function save($data, $notify_type = false)
    {
    	$notificationService = NotificationService::getInstance();
    	$owner_id_po = $data['owner_id_po'];
    	unset($data['owner_id_po']);
    	$so = new SupplyOrder();
    	foreach ($data as $key => $value) {
    		$so->data->$key = $value;
    	}
    	$so_id = $so->insert(true);
    	$userService = UserService::getInstance();
    	$from = $userService->getUserByType($owner_id_po, 'id');
    	$to = $userService->getUserByType($data['store_id'], 'chain_store');

    	if (!$notify_type) {
	    	$notify_data['from'] = $from;
	    	$notify_data['to'] = $to;
	    	$notify_data['subject_id'] = $so_id;
	    	$notificationService->save($notify_data, $notify_type);
    	}
    	return $so_id;
    }

    public function saveSOfromPO($po_id)
    {
    	$purchaseOrderService = PurchaseOrderService::getInstance();
    	$storeService = StoreService::getInstance();
    	$snapshotService = SnapshotService::getInstance();

    	$po = $purchaseOrderService->getPOByType($po_id, 'id');
    	$order_items = unserialize($po->order_items_snapshot);
    	$quantity = $store_id = $weight = $total = 0;
		foreach ($order_items as $key => $order_item) {
			$snapshot = $snapshotService->getSnapshotByType($order_item['snapshot_id'], 'id');
			$weight += $snapshot->weight * $order_item['quantity'];
			$total += $snapshot->display_price * $order_item['quantity'];
			$quantity += ($order_item['quantity'] + $order_item['redeem_quantity']);
			$store_id = $order_item['store_id'];
		}
		$store = $storeService->getStoreByType($store_id, 'id', true);
		
		$checkFee_data['province'] = $store->store_province_name;
		$checkFee_data['district'] = $store->store_district_name;
		$checkFee_data['address'] = $store->store_address;
		$checkFee_data['weight'] = $weight;
		$checkFee_data['total'] = $total;



    	$so_data['owner_id_po'] = $po->owner_id;
		$so_data['owner_id'] = $po->id;
		$so_data['type'] = "HD";
		$so_data['time_created'] = $po->time_created;
		$so_data['status'] = 0;
		$so_data['store_id'] = $store_id;
		$shippingService = ShippingService::getInstance();
		$sm = $shippingService->getMethod($po->shipping_method);
		$shipping = $sm->checkFee($checkFee_data);
		if ($shipping) {
			$so_data['shipping_fee'] = $shipping->fee->fee;
		} else {
			$so_data['shipping_fee'] = 0;
		}
		$so_data['order_items_snapshot'] = $po->order_items_snapshot;
		$so_data['total'] = $total;
		$so_data['quantity'] = $quantity;
		return $this->save($so_data);

    }

    public function getSOsByPO($po_id)
    {
    	$conditions = null;
		$conditions[] = [
			'key' => 'owner_id',
			'value' => "= '{$po_id}'",
			'operation' => ''
		];
		$sos = $this->getSOs($conditions, 0, 99999999);
		if (!$sos) return false;
		return $sos;
    }

    public function getSOByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$so = $this->getSO($conditions);
		if (!$so) return false;
		return $so;
    }

    public function getSO($conditions)
	{
		$so = $this->searchObject($conditions, 0, 1);
		if (!$so) return false;
		$so = $this->changeStructureInfo($so);
		return $so;
	}

	public function getSOs($conditions, $offset = 0, $limit = 10)
	{
		$sos = $this->searchObject($conditions, $offset, $limit);
		if (!$sos) return false;
		foreach ($sos as $key => $so) {
			$so = $this->changeStructureInfo($so);
			$sos[$key] = $so;
		}
		return array_values($sos);
	}

	private function changeStructureInfo($so)
	{
		$so->display_order = convertPrefixOrder("HD", $so->id, $so->time_created);
		return $so;
	}

}