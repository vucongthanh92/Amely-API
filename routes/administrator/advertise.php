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
	if (!array_key_exists('advertise_id', $params)) return responseError("advertise_id_not_empty");

	$ad = $advertiseService->getAdvertiseByType($params['advertise_id'], 'id');

	switch ($ad->advertise_type) {
		case 0:
			$product = $productService->getProductByType($ad->target_id, 'id');
			if (!$product) return responseError("no_data");
			$ad->product = $product;
			break;
		case 1:
			break;
		case 2:
			# code...
			break;
		default:
			return responseError("no_data");
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
	if (!array_key_exists('status', $params))  	$params['status'] = false;
	if (!array_key_exists('offset', $params))  	$params['offset'] = 0;
	if (!array_key_exists('limit', $params))  	$params['limit'] = 10;

	$ads_params = null;
	switch ($params['approved']) {
		case -1:
			# code...
			break;
		case 0:
			$ads_params[] = [
				'key' => 'approved',
				'value' => "= 0",
				'operation' => ''
			];
			break;
		case 1:
			$ads_params[] = [
				'key' => 'approved',
				'value' => "> 0",
				'operation' => ''
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
	if (!$ads) return responseError("no_data");
	return response($ads);
});