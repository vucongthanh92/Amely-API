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

		$userService = \UserService::getInstance();
		$storeService = \StoreService::getInstance();
		$supplyOrderService = \SupplyOrderService::getInstance();
		$snapshotService = \SnapshotService::getInstance();

		$so = $supplyOrderService->getSOByType($so_id, 'id');
		$user = $userService->getUserByType($creator_id, 'id', true);
		$store = $storeService->getStoreByType($so->store_id, 'id', true);

		$products = [];
		foreach ($items as $key => $item) {
			$snapshot = $snapshotService->getSnapshotByType($item['snapshot_id'], 'id');
			$parmas = null;
			$parmas['name'] = $snapshot->title;
			$parmas['weight'] = $snapshot->weight;
			$parmas['quantity'] = $item['quantity'];

			array_push($products, $parmas);
		}
		$products = json_encode($products);

		return true;
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
