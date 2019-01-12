<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->put($container['administrator'].'/count', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();
	$productService = ProductService::getInstance();
	$supplyOrderService = SupplyOrderService::getInstance();
	$deliveryOrderService = DeliveryOrderService::getInstance();
	$purchaseOrderService = PurchaseOrderService::getInstance();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('shop_id', $params)) $params['shop_id'] = '';
	if (!array_key_exists('type', $params)) $params['type'] = 'user';

	$count = 0;
	switch ($params['type']) {
		case 'user':
			# code...
			break;
		case 'shop':
			# code...
			break;
		case 'store':
			# code...
			break;
		case 'product':
			$product_params[] = [
				'key' => 'time_created',
				'value' => "> 0",
				'operation' => ''
			];
			if ($params['shop_id']) {
				$product_params[] = [
					'key' => 'owner_id',
					'value' => "= {$params['shop_id']}",
					'operation' => 'AND'
				];
			}
			$product_params[] = [
				'key' => 'approved',
				'value' => "> 0",
				'operation' => 'AND'
			];
			$product_params[] = [
		    	'key' => '*',
		    	'value' => "count",
		    	'operation' => 'count'
		    ];

		    $products = $productService->getProduct($product_params, 0, 1);
		    if (!$products) $count = 0;
    		$count = $products->count;
			break;
		case 'so':
			# code...
			break;
		case 'do':
			# code...
			break;
		case 'po':
			# code...
			break;
		case 'ads':
			# code...
			break;
		case 'promotion':
			# code...
			break;
		case 'product_group':
			# code...
			break;
		case 'categories':
			# code...
			break;
		case 'redeem':
			# code...
			break;
		default:
			$count = 0;
			break;
	}
	return response(['count' => $count]);
});