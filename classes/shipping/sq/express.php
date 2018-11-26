<?php
namespace Amely\Shipping\SQ;

class Express extends \Object
{
	private $url;
	private $ghtk_token;
	private $return_transfer;
	private $version;
	private $currency;

	function __construct()
	{
		$this->url = "https://dev.ghtk.vn";
		$this->ghtk_token = "9A95897FF196803235CC80a66aAa9ea7f396739e";
		$this->return_transfer = true;
        $this->version = CURL_HTTP_VERSION_1_1;
        $this->currency = 'VND';
	}
	
	public function process()
	{	
		$url = $this->url."services/shipment/order";

		$items = $this->items;
		$creator_id = $this->creator_id;
		$so_id = $this->so_id;

		$purchaseOrderService = \PurchaseOrderService::getInstance();
		$deliveryOrderService = \DeliveryOrderService::getInstance();
		$userService = \UserService::getInstance();
		$storeService = \StoreService::getInstance();
		$supplyOrderService = \SupplyOrderService::getInstance();
		$snapshotService = \SnapshotService::getInstance();

		$so = $supplyOrderService->getSOByType($so_id, 'id');
		$po = $purchaseOrderService->getPOByType($so->owner_id, 'id');
		$creator = $userService->getUserByType($creator_id, 'id', true);

		$store = $storeService->getStoreByType($so->store_id, 'id', true);
		$owner_store = $userService->getUserByType($store->id, 'chain_store');

		$products = [];
		$weight = $total = 0;
		foreach ($items as $key => $item) {
			$snapshot = $snapshotService->getSnapshotByType($item['snapshot_id'], 'id');
			$parmas = null;
			$parmas['name'] = $snapshot->title;
			$parmas['weight'] = $snapshot->weight;
			$parmas['quantity'] = $item['quantity'];
			$weight += $snapshot->weight * $item->quantity;
			$total += $snapshot->display_price * $item->quantity;
			array_push($products, $parmas);
		}
		$checkFee_data['province'] = $store->store_province_name;
		$checkFee_data['district'] = $store->store_district_name;
		$checkFee_data['address'] = $store->store_address;
		$checkFee_data['weight'] = $weight;
		$checkFee_data['total'] = $total;
		$shipping = $this->checkFee($checkFee_data);
		

		$do_data['owner_id'] = $creator_id;
		$do_data['type'] = 'user';
		$do_data['so_id'] = $so_id;
		$do_data['order_items_snapshot'] = $so->order_items_snapshot;
		$do_data['status'] = 0;
		$do_data['shipping_fullname'] = $po->shipping_fullname;
		$do_data['shipping_phone'] = $po->shipping_phone;
		$do_data['shipping_address'] = $po->shipping_address;
		$do_data['shipping_province'] = $po->shipping_province;
		$do_data['shipping_district'] = $po->shipping_district;
		$do_data['shipping_ward'] = $po->shipping_ward;
		$do_data['shipping_note'] = $po->shipping_note;
		$do_data['shipping_method'] = $po->shipping_method;
		$do_data['shipping_fee'] = $shipping->fee->fee;
		$do_id = $deliveryOrderService->save($do_data);

    // private $shipping_fullname;
    // private $shipping_phone;
    // private $shipping_address;
    // private $shipping_province;
    // private $shipping_district;
    // private $shipping_ward;
    // private $shipping_note;

		$order_info["id"] = $do_id;
        $order_info["pick_name"] = $owner_store->fullname;
        $order_info["pick_address"] = $store->store_address;
        $order_info["pick_province"] = $store->store_province_name;
        $order_info["pick_district"] = $store->store_district_name;
        $order_info["pick_tel"] = $po->store_phone;

        $order_info["tel"] = $po->shipping_phone;
        $order_info["name"] = $po->shipping_fullname;
        $order_info["address"] = $po->shipping_address;
        $order_info["province"] = $po->shipping_province_name;
        $order_info["district"] = $po->shipping_district_name;
        $order_info["is_freeship"] = 1;
        $order_info["pick_money"] = 0;
        $order_info["note"] = $po->shipping_note;
        
		$products = json_encode($products);
        $order_info = json_encode($order_info);

        $order = <<<HTTP_BODY
        {
            "products": {$products},
            "order": {$order_info}
        }
HTTP_BODY;

        $services = \Services::getInstance();
		$response = $services->connectServerGHTK($this->ghtk_token, $url, $parmas, "POST");

		return $response;
	}

	public function checkFee($data)
	{
		$url = $this->url."/services/shipment/fee?";
		
		$parmas = array(
			"pick_province" => $data['pick_province'],
			"pick_district" => $data['pick_district'],
			"province" => $data['province'],
			"district" => $data['district'],
			"address" => $data['address'],
			"weight" => $data['weight'],
			"value" => $data['total']
        );
		$services = \Services::getInstance();
		$response = $services->connectServerGHTK($this->ghtk_token, $url, $parmas);
		return $response;
	}

	public function redeemDelivery()
    {
    }
}
