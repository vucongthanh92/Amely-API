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

		$notificationService = \NotificationService::getInstance();
		$purchaseOrderService = \PurchaseOrderService::getInstance();
    	$supplyOrderService = \SupplyOrderService::getInstance();
    	$storeService = \StoreService::getInstance();
    	$snapshotService = \SnapshotService::getInstance();
    	$userService = \UserService::getInstance();

    	$po = $purchaseOrderService->getPOByType($po_id, 'id');
    	$to = $userService->getUserByType($po->owner_id, 'id');
    	$notify_data['from'] = $to;
    	$notify_data['to'] = $owner_cart;
    	$notify_data['subject_id'] = $po->id;
    	$notify_data['display_order'] = $po->display_order;
    	$notificationService->save($notify_data, "order:request:quickpay");

		return true;
	}

	public function getResult()
	{
		$po_id = $this->order_id;
		$status = $this->status;
		$creator = $this->creator;

		$sm->so_id = $so_id;
		$sm->creator_id = $po->owner_id;
		$sm->items = $order_items_snapshot;
		

		$shippingService = \ShippingService::getInstance();
	    $notificationService = \NotificationService::getInstance();
		$purchaseOrderService = \PurchaseOrderService::getInstance();
		$supplyOrderService = \SupplyOrderService::getInstance();
		$userService = \UserService::getInstance();
		$snapshotService = \SnapshotService::getInstance();
		$productGroupService = \ProductGroupService::getInstance();
		$shopService = \ShopService::getInstance();
		$storeService = \StoreService::getInstance();
		$walletService = \WalletService::getInstance();
		$itemService = \ItemService::getInstance();

		$po = $purchaseOrderService->getPOByType($po_id, 'id');
		switch ($status) { 
			case 0:
				
				break;
			case 1:
				$notify_type = "order:approval";
				$order_items = unserialize($po->order_items_snapshot);
		    	$blance = $quantity = $store_id = $weight = $total = 0;
				foreach ($order_items as $key => $order_item) {
					$snapshot = $snapshotService->getSnapshotByType($order_item['snapshot_id'], 'id');
					$pg = $productGroupService->getProductGroupByType($snapshot->product_group, 'id');
					$weight += $snapshot->weight * $order_item['quantity'];
					$total += $snapshot->display_price * $order_item['quantity'];
					$quantity += ($order_item['quantity'] + $order_item['redeem_quantity']);
					$sub_total = $snapshot->display_price * $order_item['quantity'];
					if ($pg->percent > 0) {
						$blance += $sub_total * (100 - $pg->percent) / 100;
					} else if ($pg->price > 0) {
						$blance += $order_item['quantity'] * $pg->price;
					}
					if ($order_item['redeem_quantity'] > 0) {
						$itemService->redeemQuantityBySnapshot($snapshot->id, $order_item['redeem_quantity'], $po->owner_id, 'user');
					}
				}
				$so_data['po'] = $po;
		    	$so_data['owner_id_po'] = $po->owner_id;
				$so_data['owner_id'] = $po->id;
				$so_data['type'] = "HD";
				$so_data['time_created'] = $po->time_created;
				$so_data['status'] = 0;
				$so_data['store_id'] = $creator->chain_store;
				$so_data['shipping_fee'] = $po->shipping_fee;
				$so_data['order_items_snapshot'] = $po->order_items_snapshot;
				$so_data['total'] = $total;
				$so_data['quantity'] = $quantity;
				$so_id = $supplyOrderService->save($so_data);
				$store = $storeService->getStoreByType($creator->chain_store, 'id');
				$shop = $shopService->getShopByType($store->owner_id, 'id');
				$walletService->deposit($shop->owner_id, $blance, 18, $so_id, "so");
				$purchaseOrderService->updateStatus($po->id, 1);

				$sm = $shippingService->getMethod($po->shipping_method);
				$sm->so_id = $so_id;
				$sm->process();


				break;
			case 2:
				$notify_type = "order:reject";
				$purchaseOrderService->updateStatus($po->id, 2);
				break;
			default:
				break;
		}

		$to = $userService->getUserByType($po->owner_id, 'id');

		$notify_data['from'] = $creator;
    	$notify_data['to'] = $to;
    	$notify_data['subject_id'] = $po_id;
    	$notify_data['display_order'] = $po->display_order;
    	$notificationService->save($notify_data, $notify_type);

		return true;
	}
}