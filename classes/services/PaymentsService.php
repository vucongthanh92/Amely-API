<?php

class PaymentsService extends Services
{
	protected static $instance = null;
	private $methods;
	private $capacities;

	public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance =  new self();
		}
		return self::$instance;
	}

	public function __construct()
	{
		$this->methods = [];
		$this->capacities = [];
		$this->table = "amely_payment";
	}

	public function getPaymentById($payment_id)
	{
		$conditions[] = [
			'key' => 'id',
			'value' => "= {$payment_id}",
			'operation' => ''
		];
		$payment = $this->getPayment($conditions);
		return $payment;
	}

	public function save($owner_id, $type, $payment_method, $options)
	{
		$payment = new Payment();
		$payment->data->owner_id = $owner_id;
		$payment->data->type = $type;
		$payment->data->payment_method = $payment_method;
		$payment->data->options = $options;
		$payment->data->status = 0;
		return $payment->insert(true);
	}
	
	public function request($payment_id, $request, $status)
	{
		$payment = new Payment();
		$payment->data->request = $request;
		$payment->data->status = $status;
		$payment->data->id = $payment_id;
		$payment->where = "id = {$payment_id}";
		return $payment->update(true);
	}

	public function response($payment_id, $response, $status)
	{
		$payment = new Payment();
		$payment->data->response = $response;
		$payment->data->status = $status;
		$payment->data->id = $payment_id;
		$payment->where = "id = {$payment_id}";
		return $payment->update(true);
	}

	public function getMethods()
	{
		return $this->methods;
	}

	public function getMethod($name)
	{
		$method_component = $this->methods[$name]['component'];
		$method_classname = $this->methods[$name]['classname'];

		$classname  = $method_component.'\\Payment\\'.$method_classname;

		$obj = new $classname();
		return object_cast($classname,$obj);
	}

	public function registerMethod(array $params)
	{
		foreach ($params['capacity'] as $capacity) 
			$this->capacities[$capacity][] = $params['filename'];
		
		$this->methods[$params['filename']] = $params;
	}

	public function findMethodsByCapacity($capacity)
	{
		$result = [];
		foreach ($this->capacities[$capacity] as $method) {
			$result[$method] = $this->methods[$method];
		}
		return $result;
	}

	public function processOrder($order_id, $order_type = 'HD')
	{
		$productGroupService = ProductGroupService::getInstance();
		$notificationService = NotificationService::getInstance();
		$shippingService = ShippingService::getInstance();
		$productService = ProductService::getInstance();
		$snapshotService = SnapshotService::getInstance();
		$supplyOrderService = SupplyOrderService::getInstance();
		$itemService = ItemService::getInstance();
		$storeService = StoreService::getInstance();
		$userService = UserService::getInstance();
		$purchaseOrderService = PurchaseOrderService::getInstance();
		$shopService = ShopService::getInstance();

		if ($order_type == 'HD') {
			$po = $purchaseOrderService->getPOByType($order_id, 'id');
			$user = $userService->getUserByType($po->owner_id, 'id', true);
			if (!$po) return false;
			$order_items = unserialize($po->order_items_snapshot);
			if (!$order_items) return false;
			$items_sos = [];
			foreach ($order_items as $key => $order_item) {
				$items_sos[$order_item['store_id']][$order_item['product_id']] = [
					'snapshot_id' => $order_item['snapshot_id'],
					'quantity' => $order_item['quantity'],
					'redeem_quantity' => $order_item['redeem_quantity']
				];
			}

			if ($items_sos) {
				foreach ($items_sos as $kitems_so => $items_so) {

					$store = $storeService->getStoreByType($kitems_so, 'id');
					$order_items_snapshot = null;
					$blance = $weight = $total = $quantity = 0;
					foreach ($items_so as $kproduct_id => $item_so) {
						$p = $productService->getProductByType($kproduct_id, 'id');
						$snapshot = $snapshotService->getSnapshotByType($item_so['snapshot_id'], 'id');
						$pg = $productGroupService->getProductGroupByType($snapshot->product_group, 'id');

						$order_items_snapshot[] = [
							'product_id' => $kproduct_id,
							'price' => $snapshot->display_price,
							'snapshot_id' => $snapshot->id,
							'store_id' => $kitems_so,
							'quantity' => $item_so['quantity'],
							'redeem_quantity' => $item_so['redeem_quantity']
						];
						if ($item_so['redeem_quantity'] > 0) {
							$itemService->redeemQuantityBySnapshot($snapshot->id, $item_so['redeem_quantity'], $user->id, 'user');
						}
						$productService->updateMostSold($p->id, ($p->number_sold + $item_so['quantity']));
						

						$quantity += $item_so['quantity'];
						$sub_total = $snapshot->display_price * $item_so['quantity'];
						$total += $sub_total;
						if ($pg->percent > 0) {
							$blance += $sub_total * (100 - $pg->percent) / 100;
						} else if ($pg->price > 0) {
							$blance += $item_so['quantity'] * $pg->price;
						}

						$weight += $snapshot->weight * $item_so['quantity'];
					}
					$fee_data = null;
					$fee_data['pick_province'] = $store->store_province_name;
					$fee_data['pick_district'] = $store->store_district_name;
					$fee_data['province'] = $po->shipping_province_name;
					$fee_data['district'] = $po->shipping_district_name;
					$fee_data['address'] = $po->shipping_full_address;
					$fee_data['weight'] = $weight;
					$fee_data['value'] = $total;
					
					$so_data['shipping_fee'] = 0;
					$shipping_fee = $shippingService->getMethod($po->shipping_method);
					$shipping = $shipping_fee->checkFee($fee_data);
					if ($shipping) {
						$so_data['shipping_fee'] = $shipping->fee->fee;
					} else {
						$so_data['shipping_fee'] = 0;
					}
					$shop = $shopService->getShopByType($store->owner_id, 'id');
					$so_data['po'] = $po;
					$so_data['owner_id_po'] = $po->owner_id;
					$so_data['owner_id'] = $po->id;
					$so_data['type'] = $order_type;
					$so_data['time_created'] = $po->time_created;
					$so_data['status'] = 0;
					$so_data['store_id'] = $kitems_so;
					$so_data['order_items_snapshot'] = serialize($order_items_snapshot);
					$so_data['total'] = $total + $so_data['shipping_fee'];
					$so_data['quantity'] = $quantity;
					$so_id = $supplyOrderService->save($so_data, "order:request");

					WalletService::getInstance()->deposit($shop->owner_id, $blance, 18, $so_id, "so");
					// $so = new SupplyOrder();
					// $so->data->time_created = $order->time_created;
					// $so->data->owner_id = $order->id;
					// $so->data->type = $order_type;
					// $so->data->status = 0;
					// $so->data->store_id = $kitems_so;
					// $so->data->shipping_fee = 0;
					// $so->data->order_items_snapshot = serialize($order_items_snapshot);
					// $so->data->total = $total;
					// $so->data->quantity = $quantity;
					// $so_id = $so->insert(true);
					if ($so_id) {
						$sm = $shippingService->getMethod($po->shipping_method);
						$time = time();
						$sm->so_id = $so_id;
						$sm->process();
					}
				}
				return true;
			}

			$owner_store = $userService->getUserByType($store->id, 'chain_store');
			$to = $userService->getUserByType($po->owner_id, 'id');

			$notify_data['from'] = 1;
	    	$notify_data['to'] = $to;
	    	$notify_data['subject_id'] = $po->id;
	    	$notify_data['display_order'] = $po->display_order;
	    	$notificationService->save($notify_data, "order:approval");
		}
		return false;
	}

	public function getPayment($conditions)
	{
		$payment = $this->searchObject($conditions, 0, 1);
		if (!$payment) return false;
		return $payment;
	}
}