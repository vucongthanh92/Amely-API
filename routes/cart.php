<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/cart', function (Request $request, Response $response, array $args) {
	$cartService = CartService::getInstance();
	$subProductService = SubProductService::getInstance();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('items', $params))  	$params['items'] = false;
	$cartService->save($params['items']);
	var_dump($_SESSION['cart']);die('1');

});