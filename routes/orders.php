<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/orders', function (Request $request, Response $response, array $args) {

	$storage = [
		'filename' => 'sq/storage',
		'component' => 'Market',
		'classname' => 'SQ\\Storage',
		'displayname' => "Kho của tôi",
		'capacity' => ['process', 'redeem']
	];

	$pickup = [
		'filename' => 'sq/pickup',
		'component' => 'Market',
		'classname' => 'SQ\\Pickup',
		'displayname' => "Nhận tại cửa hàng",
		'capacity' => ['redeem']
	];

	$express = [
		'filename' => 'sq/express',
		'component' => 'Market',
		'classname' => 'SQ\\Express',
		'displayname' => "Giao hàng tiết kiệm",
		'capacity' => ['process']
	];
	$shipping_methods['sq/storage'] = $storage;
	$shipping_methods['sq/pickup'] 	= $pickup;
	$shipping_methods['sq/express'] = $express;

	$paypal = [
		'filename' => 'paypal/standard',
		'component' => 'Market',
		'classname' => 'Paypal\\Standard',
		'displayname' => "Tài khoản Paypal",
		'capacity' => ['process','deposit','withdraw']
	];
	$opatm = [
		'filename' => 'onepay/opatm',
		'component' => 'Market',
		'classname' => 'OnePay\\OPATM',
		'displayname' => "OnePay thẻ ATM nội địa",
		'capacity' => ['process','deposit']
	];
	$opcreditcard = [
		'filename' => 'onepay/opcreditcard',
		'component' => 'Market',
		'classname' => 'OnePay\\OPCreditCard',
		'displayname' => "OnePay thẻ quốc tế Visa/Master",
		'capacity' => ['process','deposit']
	];

	$payment_methods['paypal/standard'] 	= $paypal;
	$payment_methods['onepay/opatm'] 		= $opatm;
	$payment_methods['onepay/opcreditcard'] = $opcreditcard;

	$options = [
		'paypal/standard' => null,
		'onepay/opatm' => null,
		'onepay/opcreditcard' => null
	];
    return response([
		"shipping_methods" => $shipping_methods,
		"payment_methods" => $payment_methods,
		"options" => $options
	]);

	// $shipping_methods = $shippingService->getMethods();
	// $payment_methods = $paymentsService->findMethodsByCapacity('process');
	// $options = [];
	// foreach ($payment_methods as $key => $payment_method) {
	// 	$pm = $paymentsService->getMethod($payment_method['filename']);
	// 	if ($pm->getMobileOptions()) {
	// 		$options[$payment_method['filename']] = $pm->getMobileOptions();
	// 	} else {
	// 		$options[$payment_method['filename']] = null;
	// 	}
	// }

	// return [
	// 	"shipping_methods" => $shipping_methods,
	// 	"payment_methods" => $payment_methods,
	// 	"options" => $options
	// ];
});