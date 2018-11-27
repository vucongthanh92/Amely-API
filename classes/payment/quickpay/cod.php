<?php
namespace Amely\Payment\QuickPay;

class COD extends \Object implements \Amely\Payment\IPaymentMethod
{
	public $owner_cart;
	public $order_id;
	public $description;
	public $amount;
	public $creator;
	public $order_type;
	public $payment_method;
	public $duration;
	public $payment_id;

	function __construct()
	{
		
	}

	public function process()
	{
		$po_id = $this->order_id;
		$order_type = $this->order_type;
		$creator = $this->creator;
		$owner_cart = $this->owner_cart;

		$purchaseOrderService = PurchaseOrderService::getInstance();
    	$supplyOrderService = SupplyOrderService::getInstance();
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
			if ($owner_cart->chain_store != $order_item['store_id']) return false;
		}
		$store = $storeService->getStoreByType($owner_cart->chain_store, 'id', true);
		
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
		$supplyOrderService->save($so_data, "order:request:quickpay");
	}

	public function getResult()
	{
	}
}