<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/shop/most_sold_products', function (Request $request, Response $response, array $args) {
	$loggedin_user = loggedin_user();
    $block_list = json_decode($loggedin_user->blockedusers);

    $shop_guid = input('shop_guid', false, false);
    if (!$shop_guid) return false;

	$product_params = null;
	$product_params[] = [
		'key' => 'owner_guid',
		'value' => "= {$shop_guid}",
		'operation' => ''
	];
	$product_params[] = [
		'key' => 'quantity',
		'value' => "> 0",
		'operation' => 'AND'
	];
	$product_params[] = [
		'key' => 'enabled',
		'value' => "= 1",
		'operation' => 'AND'
	];
	$product_params[] = [
		'key' => 'approved',
		'value' => "NOT IN ('new', 'suspended', 'unpublished')",
		'operation' => 'AND'
	];
	$product_params[] = [
		'key' => 'number_sold',
		'value' => "DESC",
		'operation' => 'order_by'
	];
	
	$products =  ProductsService::getInstance()->getProductsOnView($product_params, 0, 16);
	return $products;
});