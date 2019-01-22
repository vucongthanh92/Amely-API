<?php
use Slim\Http\Request;
use Slim\Http\Response;

// thong tin chi tiet 1 user
$app->post($container['administrator'].'/search', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$shopService = ShopService::getInstance();
	$productService = ProductService::getInstance();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('shop_id', $params)) $params['shop_id'] = -1;
	if (!array_key_exists('keyword', $params)) return response(false);
	if (!array_key_exists('type', $params)) $params['type'] = 'all';

	$result['products'] = [];
	$result['users'] = [];
	switch ($params['type']) {
		case 'product':
			$data_params[] = [
				'key' => 'title',
				'value' => "'%".$params['keyword']."%'",
				'operation' => 'LIKE'
			];
			if ($params['shop_id'] > 0) {
				$data_params[] = [
					'key' => 'owner_id',
					'value' => "= {$params['shop_id']}",
					'operation' => 'AND'	
				];	
			}
			$products = $productService->getProducts($data_params, 0, 999999999);
			if ($products) {
				$result['products'] = $products;
			}
			break;
		case 'user':
			$data_params[] = [
				'key' => 'username',
				'value' => "'%".$params['keyword']."%'",
				'operation' => 'LIKE'
			];
			$users = $userService->getUsers($data_params, 0, 999999999, false);
			if ($users) {
				$result['users'] = $users;
			}
			break;
		default:
			# code...
			break;
	}
	
	return response($result);
});
