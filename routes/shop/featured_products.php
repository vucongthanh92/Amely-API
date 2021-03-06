<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/shop/featured_products', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();
	$loggedin_user = loggedin_user();
    $params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("shop_id", $params)) 		$params["shop_id"] = false;
	if (!$params['shop_id']) return response(false);

	$product_params = null;
	$product_params[] = [
    	'key' => 'p.id',
    	'value' => '',
    	'operation' => 'group_by'
    ];
	$product_params[] = [
		'key' => 'p.owner_id',
		'value' => "= {$params['shop_id']}",
		'operation' => ''
	];
	$product_params[] = [
		'key' => 'p.approved',
		'value' => "> 0",
		'operation' => 'AND'
	];
	$product_params[] = [
		'key' => 'p.status',
		'value' => "= 1",
		'operation' => 'AND'
	];
	$product_params[] = [
		'key' => 'p.featured',
		'value' => "= 1",
		'operation' => 'AND'
	];
	$product_params[] = [
		'key' => 'p.product_order',
		'value' => 'DESC',
		'operation' => 'order_by'
	];
	$product_params[] = [
        'key' => 'amely_product_store ps',
        'value' => "ps.product_id = p.id",
        'operation' => 'JOIN'
    ];
    $product_params[] = [
        'key' => 'ps.quantity',
        'value' => "> 0",
        'operation' => 'AND'
    ];
    $product_params[] = [
    	'key' => 'ps.quantity',
    	'value' => '',
    	'operation' => 'query_params'
    ];
    $product_params = $productService->queryProductParams($product_params);

	$products = $productService->getProducts($product_params, 0, 16);
	if (!$products) return response(false);
	foreach ($products as $key => $product) {
		$store_quantity = ProductStoreService::getInstance()->showProduct($product->id);
		if (!$store_quantity) {
			unset($products[$key]);
			continue;
		}
	}
	if (!$products) return response(false);
	return response(array_values($products));
});