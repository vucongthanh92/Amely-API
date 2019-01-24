<?php
use Slim\Http\Request;
use Slim\Http\Response;

// xem chi tiet quang cao
$app->get($container['administrator'].'/advertise', function (Request $request, Response $response, array $args) {
	$advertiseService = AdvertiseService::getInstance();
	$productService = ProductService::getInstance();
	$shopService = ShopService::getInstance();
	$userService = UserService::getInstance();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('ad_id', $params)) return responseError(ERROR_0);

	$ad = $advertiseService->getAdvertiseByType($params['ad_id'], 'id');

	switch ($ad->advertise_type) {
		case 0:
			$product = $productService->getProductByType($ad->target_id, 'id');
			if (!$product) return responseError(ERROR_1);
			$ad->product = $product;
			break;
		case 1:
			break;
		case 2:
			# code...
			break;
		default:
			return responseError(ERROR_1);
			break;
	}

	$shop = $shopService->getShopByType($ad->owner_id, 'id');
	$creator = $userService->getUserByType($ad->creator_id, 'id', false);

	$ad->shop = $shop;
	$ad->creator = $creator;
	return response($ad);
});

// lay danh sach quang cao
$app->put($container['administrator'].'/advertise', function (Request $request, Response $response, array $args) {
	$advertiseService = AdvertiseService::getInstance();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	// lay danh sach quang cao theo id cua hang  ( null la lay tat ca)
	if (!array_key_exists('shop_id', $params))  $params['shop_id'] = false;
	/* lay danh sach quang cao theo type advertise (
		-1 lay tat ca
		0 product,
		1 shop,
        2 banner
        )
    */
	if (!array_key_exists('advertise_type', $params))  	$params['advertise_type'] = -1;
	/* lay danh sach quang cao theo trang thai duyet hoac khong duyet ( null la lay tat ca)
		-1 la lay tat ca
		0 la chua duyet
		1 la duyet
	*/
	if (!array_key_exists('approved', $params))  	$params['approved'] = -1;
	/* lay danh sach quang cao theo status(0,1) ( null la lay tat ca) 
		-1 lay tat ca
		0 la tat
		1 la mo
	*/
	if (!array_key_exists('status', $params))  	$params['status'] = -1;
	if (!array_key_exists('offset', $params))  	$params['offset'] = 0;
	if (!array_key_exists('limit', $params))  	$params['limit'] = 10;

	$ads_params = null;
	$ads_params[] = [
		'key' => 'time_created',
		'value' => "> 0",
		'operation' => ''
	];
	switch ($params['approved']) {
		case -1:
			# code...
			break;
		case 0:
			$ads_params[] = [
				'key' => 'approved',
				'value' => "= 0",
				'operation' => 'AND'
			];
			break;
		case 1:
			$ads_params[] = [
				'key' => 'approved',
				'value' => "> 0",
				'operation' => 'AND'
			];
			break;
		default:
			return response(false);
			break;
	}

	if ($params['shop_id']) {
		$ads_params[] = [
			'key' => 'owner_id',
			'value' => "= {$params['shop_id']}",
			'operation' => 'AND'
		];
	}

	if ($params['status'] >= 0) {
		$ads_params[] = [
			'key' => 'status',
			'value' => "= {$params['status']}",
			'operation' => 'AND'
		];
	} else {
		$ads_params[] = [
			'key' => 'status',
			'value' => "IN (0,1)",
			'operation' => 'AND'
		];
	}

	switch ($params['advertise_type']) {
		case -1:
			# code...
			break;
		case 0:
			$ads_params[] = [
				'key' => 'advertise_type',
				'value' => "= 0",
				'operation' => 'AND'
			];
			break;
		case 1:
			$ads_params[] = [
				'key' => 'advertise_type',
				'value' => "= 1",
				'operation' => 'AND'
			];
			break;
		case 2:
			$ads_params[] = [
				'key' => 'advertise_type',
				'value' => "= 2",
				'operation' => 'AND'
			];
			break;
		default:
			return response(false);
			break;
	}
	$ads = $advertiseService->getAdvertises($ads_params, $params['offset'], $params['limit']);
	return response($ads);
});

$app->post($container['administrator'].'/advertise', function (Request $request, Response $response, array $args) {
	$advertiseService = AdvertiseService::getInstance();
	$shopService = ShopService::getInstance();
	$walletService = WalletService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('owner_id', $params)) return responseError(ERROR_0);
	if (!array_key_exists('title', $params)) $params['title'] = "";
	if (!array_key_exists('description', $params)) $params['description'] = "";
	if (!array_key_exists('advertise_type', $params)) $params['advertise_type'] = 0;
	if (!array_key_exists('time_type', $params)) $params['time_type'] = 0;
	if (!array_key_exists('target_id', $params)) $params['target_id'] = 0;
	if (!array_key_exists('image', $params)) $params['image'] = 0;
	if (!array_key_exists('budget', $params)) $params['budget'] = 0;
	if (!array_key_exists('cpc', $params)) $params['cpc'] = 0;
	if (!array_key_exists('link', $params)) $params['link'] = 0;
	if (!array_key_exists('amount', $params)) return responseError(ERROR_0);
	if (!array_key_exists('start_time', $params)) return responseError(ERROR_0);
	if (!array_key_exists('end_time', $params)) return responseError(ERROR_0);

	if ($params['start_time'] > $params['end_time']) return responseError(ERROR_0);
	

	$shop = $shopService->getShopByType($params['owner_id'], 'id');
	

	switch ($params['advertise_type']) {
		case 0:
			$productService = ProductService::getInstance();
			$product = $productService->getProductByType($params['target_id'], 'id');
			if (!$product) return response(false);
			if ($product->approved == 0) return response(false);
			if ($product->status != 1) return response(false);
			break;
		case 1:
			$shopService = ShopService::getInstance();
			$shop = $shopService->getShopByType($params['target_id'], 'id');
			if (!$shop) return response(false);
			if ($shop->status != 1) return response(false);
			break;
		case 2:
			
			break;
		default:
			# code...
			break;
	}

	$ad_data = [];
	$ad_data['owner_id'] = $params['owner_id'];
	$ad_data['type'] = $params['type'];
	$ad_data['title'] = $params['title'];
	$ad_data['description'] = $params['description'];
	$ad_data['advertise_type'] = $params['advertise_type'];
	$ad_data['time_type'] = $params['time_type'];
	$ad_data['target_id'] = $params['target_id'];
	$ad_data['budget'] = $params['budget'];
	$ad_data['cpc'] = $params['cpc'];
	$ad_data['link'] = $params['link'];
	$ad_data['amount'] = $params['amount'];
	$ad_data['start_time'] = $params['start_time'];
	$ad_data['end_time'] = $params['end_time'];
	$ad_data['creator_id'] = $loggedin_user->id;


	$uploadedFiles = $request->getUploadedFiles();
    $image = false;
    if ($uploadedFiles) {
	    $uploadedFile = $uploadedFiles['image'];
	    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
	        $files = $request->getUploadedFiles();
	        $image = $files['image'];
	    }
    }

	if ($loggedin_user->type == 'admin') {
		return response($advertiseService->save($ad_data, $image));
	} else {
		$wallet = $walletService->getWalletByOwnerId($loggedin_user->id);
		if ($wallet->balance >= $params['budget']) {
			$ad_id = $advertiseService->save($ad_data, $image);
			if ($ad_id) {
				switch ($params['advertise_type']) {
					case 0:
						$status = 20;
						break;
					case 1:
						$status = 21;
						break;
					case 2:
						# code...
						break;
					default:
						$status = 17;
						break;
				}
				$walletService->withdraw($loggedin_user->id, $params['budget'], $status, $ad_id, 'wallet');
			}
		}
		return response(false);
	}
});

$app->delete($container['administrator'].'/advertise', function (Request $request, Response $response, array $args) {
	$advertiseService = AdvertiseService::getInstance();
	$productService = ProductService::getInstance();
	$shopService = ShopService::getInstance();
	$userService = UserService::getInstance();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('ad_id', $params)) return responseError(ERROR_0);

	$ad = $advertiseService->getAdvertiseByType($params['ad_id'], 'id');

	if (!$ad) return response(false);

	return response($advertiseService->updateStatus($ad->id, 2));
});