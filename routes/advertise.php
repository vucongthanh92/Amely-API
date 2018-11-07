<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/advertise', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('username', $params))  	$params['username'] = false;
	if (!array_key_exists('password', $params))  	$params['password'] = false;
	if (!array_key_exists('code', $params))  	$params['code'] = false;
	if (!array_key_exists('email', $params))  	$params['email'] = false;
	if (!array_key_exists('mobilelogin', $params))  	$params['mobilelogin'] = false;

	$type = false;
	$input = false;
	if ($params['email']) {
		$type = 'email';
		$input = $params['email'];
	}
	if ($params['mobilelogin']) {
		$type = 'mobilelogin';
		$input = $params['mobilelogin'];
	}
	if (!$input || !$type) return response(false);
	$user = $userService->getUserByType($input, $type, false, false);
	if (!$user) return response(false);
	$user = object_cast("User", $user);
	if ($user->verification_code == $params['code']) {
		if ($user->activation) {
			$user->data->activation = '';
		}
		$user->data->verification_code = '';
		return response($user->update());
	}
	return response(false);
});

$app->put($container['prefix'].'/advertise', function (Request $request, Response $response, array $args) {
	$advertiseService = AdvertiseService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('owner_id', $params)) $params['owner_id'] = 1;
	if (!array_key_exists('type', $params)) $params['type'] = "shop";
	if (!array_key_exists('title', $params)) $params['title'] = "";
	if (!array_key_exists('description', $params)) $params['description'] = "";
	if (!array_key_exists('advertise_type', $params)) $params['advertise_type'] = 0;
	if (!array_key_exists('time_type', $params)) $params['time_type'] = 0;
	if (!array_key_exists('target_id', $params)) $params['target_id'] = 0;
	if (!array_key_exists('image', $params)) $params['image'] = 0;
	if (!array_key_exists('budget', $params)) $params['budget'] = 0;
	if (!array_key_exists('cpc', $params)) $params['cpc'] = 0;
	if (!array_key_exists('link', $params)) $params['link'] = 0;
	if (!array_key_exists('amount', $params)) $params['amount'] = 0;
	if (!array_key_exists('start_time', $params)) $params['start_time'] = 0;
	if (!array_key_exists('end_time', $params)) $params['end_time'] = 0;
	if ($params['start_time'] > $params['end_time']) return response(false);
	switch ($params['advertise_type']) {
		case 0:
			$productService = ProductService::getInstance();
			$product = $productService->getProductByType($params['target_id'], 'id');
			if (!$product) return response(false);
			if ($product->approved != 1) return response(false);
			if ($product->enabled != 1) return response(false);
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

	$data = [];
	$data['owner_id'] = $params['owner_id'];
	$data['type'] = $params['type'];
	$data['title'] = $params['title'];
	$data['description'] = $params['description'];
	$data['advertise_type'] = $params['advertise_type'];
	$data['time_type'] = $params['time_type'];
	$data['target_id'] = $params['target_id'];
	$data['image'] = $params['image'];
	$data['budget'] = $params['budget'];
	$data['cpc'] = $params['cpc'];
	$data['link'] = $params['link'];
	$data['amount'] = $params['amount'];
	$data['start_time'] = $params['start_time'];
	$data['end_time'] = $params['end_time'];
	$data['creator_id'] = $loggedin_user->id;

	return response($advertiseService->save($data));
});