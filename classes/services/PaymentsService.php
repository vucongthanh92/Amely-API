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
		if ($order_type == 'HD') {
			$purchaseOrderService = PurchaseOrderService::getInstance();
			$order = $purchaseOrderService->getPOByType($order_id, 'id');
			if (!$order) return false;
			$order_items = unserialize($order->order_item_snapshot);
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
					$order_item_snapshot = null;
					foreach ($items_so as $kitem_so => $item_so) {
						$order_item_snapshot[] = [
							'product_id' => $kitem_so,
							'snapshot_id' => $item_so['snapshot_id'],
							'store_id' => $kitems_so,
							'quantity' => $item_so['quantity'],
							'redeem_quantity' => $item_so['redeem_quantity']
						];
					}
					$so = new SupplyOrder();
					$so->data->owner_id = $order->id;
					$so->data->type = $order_type;
					$so->data->status = 0;
					$so->data->store_id = $kitems_so;
					$so->data->shipping_fee = 0;
					$so->data->order_item_snapshot = serialize($order_item_snapshot);
					$so->insert();
				}
				return response(true);
			}



		}
		die('2');
	}
}