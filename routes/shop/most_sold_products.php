<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/shop/most_sold_products', function (Request $request, Response $response, array $args) {
	$loggedin_user = loggedin_user();
    $params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("shop_guid", $params)) 		$params["shop_guid"] = false;

    $shop_guid = $$params["shop_guid"];
    if (!$shop_guid) return response(false);

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
	
	$products = $select->getProducts($product_params, 0, 16);
	return response($products);
});