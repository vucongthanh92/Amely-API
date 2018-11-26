<?php
namespace Amely\Payment\QuickPay;

class COD extends \Object implements \Amely\Payment\IPaymentMethod
{
	


	public $order_id;
	public $description;
	public $amount;
	public $creator;
	public $order_type;
	public $payment_method;
	public $duration;
	public $payment_id;

	function __construct()
	{
		
	}

	public function process()
	{
	}

	public function getResult()
	{
	}
}