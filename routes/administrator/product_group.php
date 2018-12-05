<?php
use Slim\Http\Request;
use Slim\Http\Response;

// thong tin chi tiet 1 nganh hang
$app->get($container['administrator'].'/product_group', function (Request $request, Response $response, array $args) {
	$productGroupService = ProductGroupService::getInstance();
	$userService = UserService::getInstance();

	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('pg_id', $params)) return responseError("pg_id_not_empty");

	$pg = $productGroupService->getProductGroupByType($params['pg_id'], 'id');
	if (!$pg) return responseError("not_data");
	$user = $userService->getUserByType($pg->owner_id, 'id', false);
	$pg->owner = $user;

	return response($pg);
});

// them moi nganh hang
$app->post($container['administrator'].'/product_group', function (Request $request, Response $response, array $args) {
	$productGroupService = ProductGroupService::getInstance();
	$userService = UserService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('title', $params)) $params['title'] = "";
	if (!array_key_exists('description', $params)) $params['description'] = "";
	if (!array_key_exists('percent', $params)) $params['percent'] = false;
	if (!array_key_exists('price', $params)) $params['price'] = false;
	if (!array_key_exists('status', $params)) $params['status'] = 0;

	$pg_data['owner_id'] = $loggedin_user->id;
	$pg_data['title'] = $params['title'];
	$pg_data['description'] = $params['description'];
	$pg_data['percent'] = $params['percent'];
	$pg_data['price'] = $params['price'];
	$pg_data['status'] = $params['status'];

	return response($productGroupService->save($pg_data));
});

// lay danh sach nganh hang
$app->put($container['administrator'].'/product_group', function (Request $request, Response $response, array $args) {
	$productGroupService = ProductGroupService::getInstance();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	/* status
		-1 lay tat ca
		0 tat
		1 mo
	*/
	if (!array_key_exists('status', $params)) $params['status'] = -1;
	if (!array_key_exists('offset', $params)) $params['offset'] = 0;
	if (!array_key_exists('limit', $params)) $params['limit'] = 10;

	$pg_params = null;
	if ($params['status'] >= 0) {
		$pg_params[] = [
			'key' => 'status',
			'value' => "= {$params['status']}",
			'operation' => ''
		];
	}
	$pgs = $productGroupService->getProductGroups($pg_params, $params['offset'], $params['limit']);
	if (!$pgs) return responseError("no_data");
	return response($pgs);
});

// xoa nganh hang
$app->delete($container['administrator'].'/product_group', function (Request $request, Response $response, array $args) {
	$productGroupService = ProductGroupService::getInstance();
	$userService = UserService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('pg_id', $params)) return responseError("pg_id_not_empty");
	if ($loggedin_user->type != 'admin') return responseError("permission");

	$pg = $productGroupService->getProductGroupByType($params['pg_id'], 'id');
	if (!$pg) return responseError("not_data");
	
	$pg = object_cast("ProductGroup", $pg);
	$pg->status = 2;
	$pg->where = "id = {$pg->id}";
	return response($pg->update());
});