<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/orders', function (Request $request, Response $response, array $args) {
	$paymentsService = PaymentsService::getInstance();
	$shippingService = ShippingService::getInstance();

    return response([
		"shipping_methods" => $shippingService->getMethods(),
		"payment_methods" => $paymentsService->findMethodsByCapacity('process')
	]);

});

$app->put($container['prefix'].'/orders', function (Request $request, Response $response, array $args) {
	$paymentsService = PaymentsService::getInstance();
	$pm = $paymentsService->getMethod('onepay/opcreditcard');
	var_dump($pm->process());
	die('1234');
});