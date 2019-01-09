<?php
use Slim\Http\Request;
use Slim\Http\Response;

// thong tin he thong
$app->get($container['administrator'].'/dashboard', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();
	$walletService = WalletService::getInstance();
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();
	$userService = UserService::getInstance();
	$supplyOrderService = SupplyOrderService::getInstance();

	$loggedin_user = loggedin_user();


	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('shop_id', $params)) $params['shop_id'] = false;

    $result = [];

    $result['product_pending'] = 0;
    $result['product_approved'] = 0;
    $result['shop_pending'] = 0;
    $result['shop_approved'] = 0;
    $result['user_unactive'] = 0;
    $result['user_active'] = 0;
    $result['order'] = 0;
    $result['total_amount'] = 0;


	$wallet = $walletService->getWalletByOwnerId($loggedin_user->id);
    if (!$wallet) return response($result);

	$product_approved_params[] = [
		'key' => 'time_created',
		'value' => "> 0",
		'operation' => ''
	];
	if ($params['shop_id']) {
		$product_approved_params[] = [
			'key' => 'owner_id',
			'value' => "= {$params['shop_id']}",
			'operation' => 'AND'
		];
	}
	$product_approved_params[] = [
		'key' => 'approved',
		'value' => "> 0",
		'operation' => 'AND'
	];
	$product_approved_params[] = [
    	'key' => '*',
    	'value' => "count",
    	'operation' => 'count'
    ];

    $products_approved = $productService->getProduct($product_approved_params, 0, 1);
    if (!$products_approved) return response($result);

    $product_pending_params[] = [
		'key' => 'time_created',
		'value' => "> 0",
		'operation' => ''
	];
    if ($params['shop_id']) {
	    $product_pending_params[] = [
			'key' => 'owner_id',
			'value' => "= {$params['shop_id']}",
			'operation' => 'AND'
		];
	}
	$product_pending_params[] = [
		'key' => 'approved',
		'value' => "= 0",
		'operation' => 'AND'
	];
	$product_pending_params[] = [
    	'key' => '*',
    	'value' => "count",
    	'operation' => 'count'
    ];

    $products_pending = $productService->getProduct($product_pending_params);
    if (!$products_pending) return response($result);

    $shop_approved_params[] = [
    	'key' => 'approved',
    	'value' => "> 0",
    	'operation' => ''
    ];
    $shop_approved_params[] = [
    	'key' => '*',
    	'value' => "count",
    	'operation' => 'count'
    ];

    $shops_approved = $shopService->getShop($shop_approved_params);
    if (!$shops_approved) return response($result);

    $shop_pending_params[] = [
    	'key' => 'approved',
    	'value' => "= 0",
    	'operation' => ''
    ];
    $shop_pending_params[] = [
    	'key' => '*',
    	'value' => "count",
    	'operation' => 'count'
    ];

    $shops_pending = $shopService->getShop($shop_pending_params);
    if (!$shops_pending) return response($result);

    $user_unactive_params[] = [
    	'key' => 'activation',
    	'value' => "<> ''",
    	'operation' => ''
    ];
    $user_unactive_params[] = [
    	'key' => '*',
    	'value' => "count",
    	'operation' => 'count'
    ];
    $users_unactive = $userService->getUser($user_unactive_params, false, true);
    if (!$users_unactive) return response($result);

    $user_active_params[] = [
    	'key' => 'activation',
    	'value' => "= ''",
    	'operation' => ''
    ];
    $user_active_params[] = [
    	'key' => '*',
    	'value' => "count",
    	'operation' => 'count'
    ];
    $users_active = $userService->getUser($user_active_params, false, true);
    if (!$users_active) return response($result);

    $so_params[] = [
    	'key' => '*',
    	'value' => "count",
    	'operation' => 'count'
    ];
    if ($params['shop_id']) {
    	$store_params[] = [
    		'key' => 'owner_id',
    		'value' => "= {$params['shop_id']}",
    		'operation' => ''
    	];
    	$stores = $storeService->getStores($store_params, 0, 999999, false);
    	$stores_id = null;
    	foreach ($stores as $key => $store) {
    		array_push($stores_id, $store->id);
    	}
    	$stores_id = implode(',', $stores_id);
	    $so_params[] = [
	    	'key' => 'store_id',
	    	'value' => "IN ({$stores_id})",
	    	'operation' => ''
	    ];
    }
	
	$sos = $supplyOrderService->getSO($so_params);
    if (!$sos) return response($result);

	$result['product_pending'] = $products_pending->count;
	$result['product_approved'] = $products_approved->count;
	$result['shop_pending'] = $shops_pending->count;
	$result['shop_approved'] = $shops_approved->count;
	$result['user_unactive'] = $users_unactive->count;
	$result['user_active'] = $users_active->count;
	$result['order'] = $sos->count;
	$result['total_amount'] = $wallet->balance;
	return response($result);
});