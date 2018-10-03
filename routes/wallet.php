<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/wallet', function (Request $request, Response $response, array $args) {
	$walletService = WalletService::getInstance();
	$loggedin_user = loggedin_user();

	$wallet = $walletService->getWalletByOwnerId($loggedin_user->id);
	if (!$wallet) return response(false);
	return response($wallet);

});

$app->put($container['prefix'].'/wallet', function (Request $request, Response $response, array $args) {
	$walletService = WalletService::getInstance();
	$loggedin_user = loggedin_user();
	$wallet = $walletService->getWalletByOwnerId($loggedin_user->id);
	if ($wallet) return response(false);
	$wallet = new Wallet();
	$wallet->data->owner_id = $loggedin_user->id;
	$wallet->data->type = 'user';
	$wallet->data->title = "";
	$wallet->data->description = "";
	$wallet->data->balance = 0;
	$wallet->data->currency = "VND";
	return response($wallet->insert());

});

