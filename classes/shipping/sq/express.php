<?php
namespace Amely\Shipping\SQ;

class Express extends \Object
{
	private $url;
	private $ghtk_token;
	private $return_transfer;
	private $version;
	private $currency;

	public $test;
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

	public function checkFee()
	{
		$url = $this->url."/services/shipment/fee?";
		$data = array(
			"pick_province" => "Hà Nội",
			"pick_district" => "Quận Hai Bà Trưng",
			"province" => "Hà nội",
			"district" => "Quận Cầu Giấy",
			"address" => "P.503 tòa nhà Auu Việt, số 1 Lê Đức Thọ",
			"weight" => 1000,
			"value" => 3000000,
			"transport" => "fly"
        );
		$services = \Services::getInstance();
		$response = $services->connectServerGHTK($this->ghtk_token, $url, $data);
		return $response;
	}

	public function redeemDelivery()
    {
    }
}
