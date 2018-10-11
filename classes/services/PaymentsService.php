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
		$productService = ProductService::getInstance();
		$productDetailService = ProductDetailService::getInstance();
		$snapshotService = SnapshotService::getInstance();
		$inventoryService = InventoryService::getInstance();
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
						if ($product->product_snapshot != $item_so['snapshot_id']) return false;
						
						$order_items_snapshot[] = [
							'product_id' => $kproduct_id,
							'price' => $product->display_price,
							'pdetail_id' => $product->owner_id,
							'snapshot_id' => $item_so['snapshot_id'],
							'store_id' => $kitems_so,
							'quantity' => $item_so['quantity'],
							'redeem_quantity' => $item_so['redeem_quantity']
						];
						$quantity += $item_so['quantity'];
						$total += $product->display_price * $item_so['quantity'];
					}
					
					$so = new SupplyOrder();
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
						foreach ($order_items_snapshot as $order_item_snapshot) {
							$inventoryService->saveItem($order->owner_id, 'user', $order_item_snapshot, $so_id);
						}
					}
				}
				return true;
			}
		}
		return false;
	}
}