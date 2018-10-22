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
		$data = array(
			"pick_province" => $data['pick_province'],
			"pick_district" => $data['pick_district'],
			"province" => $data['province'],
			"district" => $data['district'],
			"address" => $data['address'],
			"weight" => $data['weight'],
			"value" => $data['total']
        );
		$services = \Services::getInstance();
		$response = $services->connectServerGHTK($this->ghtk_token, $url, $data);
		return $response;
	}

	public function redeemDelivery()
    {
    }
}
