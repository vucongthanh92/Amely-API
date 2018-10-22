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
		
	}

	public function checkFee($data)
	{
		$url = $this->url."/services/shipment/fee?";
		foreach ($data as $key => $value) {
			$parmas = array(
				"pick_province" => $value['pick_province'],
				"pick_district" => $value['pick_district'],
				"province" => $value['province'],
				"district" => $value['district'],
				"address" => $value['address'],
				"weight" => $value['weight'],
				"value" => $value['total']
	        );
			$services = \Services::getInstance();
			$response = $services->connectServerGHTK($this->ghtk_token, $url, $parmas);
			$data[$key]['fee'] = $response->fee->fee;
		}
		return $data;
	}

	public function redeemDelivery()
    {
    }
}
