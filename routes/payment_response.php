<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/payment_response', function (Request $request, Response $response, array $args) {
	$transactionService = TransactionService::getInstance();
	$purchaseOrderService = PurchaseOrderService::getInstance();
	$paymentsService = PaymentsService::getInstance();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('method', $params)) 	$params['method'] = false;
	if (!$params['method']) return response(false);

	switch ($params['method']) {
		case 'opcreditcard':
			$method = "onepay/opcreditcard";
			break;
		case 'opatm':
			$method = "onepay/opatm";
			break;
		case 'paypal':
			# code...
			break;
		default:
			return response(false);
			break;
	}

	$pm = $paymentsService->getMethod($method);
	$response = $pm->getResult();
	$po = $purchaseOrderService->getPOByType($response['order_id'], 'id');

	$transaction_params['owner_id'] = $po->owner_id;
	$transaction_params['type'] = 'user';
	$transaction_params['title'] = "";
	$transaction_params['description'] = "";
	$transaction_params['subject_type'] = 'order';
	$transaction_params['subject_id'] = $po->id;
	switch ($response['status']) {
		case 0:
			$transaction_params['status'] = 11;
			break;
		case 1:
			$transaction_params['status'] = 12;
			break;
		case 2:
			$transaction_params['status'] = 13;
			break;
		default:
			# code...
			break;
	}
	return response($transactionService->save($transaction_params));

})->setName('payment_response');