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
		$payment->where = "id = {$payment_id}";
		return $payment->update(true);
	}

	public function response($payment_id, $response, $status)
	{
		$payment = new Payment();
		$payment->data->response = $response;
		$payment->data->status = $status;
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
		$shippingService = ShippingService::getInstance();
		$productService = ProductService::getInstance();
		$snapshotService = SnapshotService::getInstance();
		$itemService = ItemService::getInstance();
		if ($order_type == 'HD') {
			$purchaseOrderService = PurchaseOrderService::getInstance();
			$order = $purchaseOrderService->getPOByType($order_id, 'id');
			if (!$order) return false;
			$order_items = unserialize($order->order_items_snapshot);
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
					$order_items_snapshot = null;
					$total = $quantity = 0;
					foreach ($items_so as $kproduct_id => $item_so) {
						$product = $productService->getProductByType($kproduct_id, 'id');
						if ($product->snapshot_id != $item_so['snapshot_id']) return false;
						
						$order_items_snapshot[] = [
							'product_id' => $kproduct_id,
							'price' => $product->display_price,
							'snapshot_id' => $product->snapshot_id,
							'store_id' => $kitems_so,
							'quantity' => $item_so['quantity'],
							'redeem_quantity' => $item_so['redeem_quantity']
						];
						$quantity += $item_so['quantity'];
						$total += $product->display_price * $item_so['quantity'];
					}
					
					$so = new SupplyOrder();
					$so->data->time_created = $order->time_created;
					$so->data->owner_id = $order->id;
					$so->data->type = $order_type;
					$so->data->status = 0;
					$so->data->store_id = $kitems_so;
					$so->data->shipping_fee = 0;
					$so->data->order_items_snapshot = serialize($order_items_snapshot);
					$so->data->total = $total;
					$so->data->quantity = $quantity;
					$so_id = $so->insert(true);
					if ($so_id) {
						$time = time();
						$sp = $shippingService->getMethod($order->shipping_method);
						$sp->so_id = $so_id;
						$sp->creator_id = $order->owner_id;
						$sp->items = $order_items_snapshot;
						$sp->process();
					}
				}
				return true;
			}
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