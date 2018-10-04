<?php
namespace Amely\Payment;

interface IPaymentMethod
{
	public function getResult();
	public function process();
	// public function deposit();
	// public function lastOrderId();
	// public function lastAmount();
	// public function lastSubTotal();
	// public function lastTax();
	// public static function callback();
	// public function getMobileOptions();
	// public function processMobile($to_guid);
	// public function payment();
}