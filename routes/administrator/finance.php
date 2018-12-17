<?php
use Slim\Http\Request;
use Slim\Http\Response;

// xem chi tiet don hang
$app->put($container['administrator'].'/finance', function (Request $request, Response $response, array $args) {
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();
	$supplyOrderService = SupplyOrderService::getInstance();
	$transactionService = TransactionService::getInstance();
	$walletService = WalletService::getInstance();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	/* shop_id 
		false la lay theo store_id
	*/
	if (!array_key_exists('shop_id', $params))  return responseError(ERROR_0);

	if (!array_key_exists('start_date', $params))  $params['start_date'] = false;
	if (!array_key_exists('end_date', $params))  $params['end_date'] = false;

	$shop = $shopService->getShopByType($params['shop_id'], 'id', false);
	$wallet = $walletService->getWalletByOwnerId($shop->owner_id);

	$transaction_params[] = [
		'key' => 'owner_id',
		'value' => "= '{$wallet->id}'",
		'operation' => ''
	];
	$transaction_params[] = [
		'key' => 'type',
		'value' => "= 'wallet'",
		'operation' => 'AND'
	];
	if ($params['start_date']) {
		$transaction_params[] = [
			'key' => 'time_created',
			'value' => ">= {$params['start_date']}",
			'operation' => 'AND'
		];
	}

	if ($params['end_date']) {
		$transaction_params[] = [
			'key' => 'time_created',
			'value' => "<= {$params['end_date']}",
			'operation' => 'AND'
		];
	}

	$transactions = $transactionService->getTransactions($transaction_params, 0, 9999999999);
	
	return response($transactions);
});