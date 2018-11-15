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
	return response($walletService->save($loggedin_user->id));
});

