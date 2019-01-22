<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/most_sold_products', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();
	$loggedin_user = loggedin_user();
	$product_params = null;
	$product_params[] = [
		'key' => 'p.number_sold',
		'value' => "> 0",
		'operation' => ''
	];
	$product_params[] = [
		'key' => 'p.status',
		'value' => "= 1",
		'operation' => 'AND'
	];
	$product_params[] = [
		'key' => 'p.approved',
		'value' => "> 0",
		'operation' => 'AND'
	];
	$product_params[] = [
		'key' => 'p.number_sold',
		'value' => "DESC",
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
	$products =  $productService->getProducts($product_params, 0, 16);
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