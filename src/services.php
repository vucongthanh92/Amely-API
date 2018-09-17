<?php

class Services
{
	protected static $instance = null;

    public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance =  new self();
		}
		return self::$instance;
	}

	public function sendCodeByMobile($mobile, $code, $message = false)
	{
		if (!$message) $message = $code;
		if ($message) $message = $message.$code;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, SMS);
		curl_setopt($ch, CURLOPT_POST, 1);
		$phone = preg_replace("/^0/i", "84", $mobile);
		curl_setopt($ch, CURLOPT_POSTFIELDS,
            "sms=sms&message=$message&phone=$phone");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($ch);
		curl_close ($ch);
		return true;
	}
}