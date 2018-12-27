<?php
use Slim\Http\Request;
use Slim\Http\Response;


/*
	trao doi
	tang qua
	mua sam
	giao hang
	su dung

*/
$app->post($container['prefix'].'/transaction', function (Request $request, Response $response, array $args) {
	$transactionService = TransactionService::getInstance();
	$purchaseOrderService = PurchaseOrderService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('offset', $params))  	$params['offset'] = 0;
	if (!array_key_exists('limit', $params))  	$params['limit'] = 0;
	if (!array_key_exists('subject_type', $params))  	$params['subject_type'] = false;
	if (!array_key_exists('owner_id', $params))  	$params['owner_id'] = $loggedin_user->id;
	if (!array_key_exists('owner_type', $params))  	$params['owner_type'] = 'user';

	$subjects_type = ['offer','gift','order','delivery','redeem', 'wallet'];
	if (!in_array($params['subject_type'], $subjects_type)) return response(false);
	$conditions[] = [
		'key' => 'time_created',
		'value' => "DESC",
		'operation' => 'order_by'
	];
	switch ($params['subject_type']) {
		case 'offer':
			$conditions[] = [
				'key' => 'owner_id',
				'value' => "= '{$params['owner_id']}'",
				'operation' => ''
			];
			$conditions[] = [
				'key' => 'type',
				'value' => "= '{$params['owner_type']}'",
				'operation' => 'AND'
			];
			$conditions[] = [
				'key' => 'subject_type',
				'value' => "IN ('offer', 'counter')",
				'operation' => 'AND'
			];
			$transactions = $transactionService->getTransactions($conditions, $params['offset'], $params['limit']);
			break;
		case 'wallet':
			$conditions[] = [
				'key' => 'owner_id',
				'value' => "= '{$loggedin_user->id}'",
				'operation' => ''
			];
			$conditions[] = [
				'key' => 'type',
				'value' => "= 'wallet'",
				'operation' => 'AND'
			];
			$transactions = $transactionService->getTransactions($conditions, $params['offset'], $params['limit']);
			break;
		case 'order':
			$conditions[] = [
				'key' => 'owner_id',
				'value' => "= '{$params['owner_id']}'",
				'operation' => ''
			];
			$conditions[] = [
				'key' => 'type',
				'value' => "= '{$params['owner_type']}'",
				'operation' => 'AND'
			];
			$conditions[] = [
				'key' => 'subject_type',
				'value' => "= '{$params['subject_type']}'",
				'operation' => 'AND'
			];
			$transactions = $transactionService->getTransactions($conditions, $params['offset'], $params['limit']);
			foreach ($transactions as $key => $transaction) {
				$po = $purchaseOrderService->getPOByType($transaction->subject_id, 'id');
				$transaction->display_order = $po->display_order;
				$transactions[$key] = $transaction;
			}
			break;
		default:
			$conditions[] = [
				'key' => 'owner_id',
				'value' => "= '{$params['owner_id']}'",
				'operation' => ''
			];
			$conditions[] = [
				'key' => 'type',
				'value' => "= '{$params['owner_type']}'",
				'operation' => 'AND'
			];
			$conditions[] = [
				'key' => 'subject_type',
				'value' => "= '{$params['subject_type']}'",
				'operation' => 'AND'
			];
			$transactions = $transactionService->getTransactions($conditions, $params['offset'], $params['limit']);
			break;
	}

	if (!$transactions) return response(false);
	return response($transactions);
});