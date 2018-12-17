<?php
use Slim\Http\Request;
use Slim\Http\Response;

// thong tin he thong
$app->get($container['administrator'].'/dashboard', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();

	$loggedin_user = loggedin_user();

	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('shop_id', $params)) $params['shop_id'] = false;

	$product_params[] = [
		'key' => 'snapshot_id',
		'value' => "> 0",
		'operation' => ''
	];

	// if ($params['shop_id']) {
	// 	$product_params[] = [
	// 		'key' => 'owner_id',
	// 		'value' => "= {$params['shop_id']}",
	// 		'operation' => 'AND'
	// 	];
	// }

	// $product_params[] = [
	// 	'key' => '*',
	// 	'value' => "count",
	// 	'operation' => "count"
	// ];

	


	$result = [];
	$result['product_pending'] = 0;
	$result['product_approved'] = 0;
	$result['shop_pending'] = 0;
	$result['shop_approved'] = 0;
	$result['user_unactive'] = 0;
	$result['user_active'] = 0;
	$result['order'] = 0;
	$result['total_amount'] = 0;
	return response($result);
});