<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/payment_response', function (Request $request, Response $response, array $args) {
	$paymentsService = PaymentsService::getInstance();
	$pm = $paymentsService->getMethod('onepay/opcreditcard');
	$pm->getResult();
	die('12');
})->setName('payment_response');