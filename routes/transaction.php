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
	if ($params['subject_type'] == 'offer') {
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
	} else {
		$transactions = $transactionService->getTransactionsByType($params['owner_id'], $params['owner_type'], $params['subject_type']);
	}
	if (!$transactions) return response(false);
	return response($transactions);
});