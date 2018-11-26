<?php
namespace Amely\Payment\QuickPay;

class COS extends \Object implements \Amely\Payment\IPaymentMethod
{
	public $owner_cart;
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
		$order_id = $this->order_id;
		$order_type = $this->order_type;
		$creator = $this->creator;
		$owner_cart = $this->owner_cart;

		$notificationService = \NotificationService::getInstance();
		$notify_data['to'] = $owner_cart;
		$notify_data['from'] = $creator;
		$notify_data['subject_id'] = $order_id;
		$notificationService->save($data, "order:request:quickpay");

		return true;
	}

	public function getResult()
	{
	}
}