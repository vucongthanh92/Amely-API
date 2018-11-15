<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/categories', function (Request $request, Response $response, array $args) {
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('counter_id', $params)) 	$params['counter_id'] = 0;
});

$app->post($container['prefix'].'/categories', function (Request $request, Response $response, array $args) {
	$categoryService = CategoryService::getInstance();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('offset', $params)) $params['offset'] = 0;
	if (!array_key_exists('limit', $params)) $params['limit'] = 10;
	if (!array_key_exists('shop_id', $params)) $params['shop_id'] = false;
	if (!array_key_exists('type', $params)) $params['type'] = 'market';
	$offset = (double)$params['offset'];
	$limit = (double)$params['limit'];
	$shop_id = $params['shop_id'];
	$type = $params['type'];

	$category_params[] = [
		'key' => 'CAST(`sort_order` AS SIGNED)',
		'value' => "ASC",
		'operation' => 'order_by'
	];

	if ($shop_id) {
		$category_params[] = [
			'key' => 'owner_id',
			'value' => "= {$shop_id}",
			'operation' => ''
		];
		$category_params[] = [
			'key' => 'type',
			'value' => "= 'shop'",
			'operation' => 'AND'
		];
	} else {
		$category_params[] = [
			'key' => 'subtype',
			'value' => "= {$params['type']}",
			'operation' => ''
		];
	}

	$categories = $categoryService->getCategories($category_params, $offset, $limit);
	if (!$categories) return response(false);
	return response($categories);
});

$app->put($container['prefix'].'/categories', function (Request $request, Response $response, array $args) {
	$categoryService = CategoryService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('owner_id', $params)) $params['owner_id'] = 0;
	if (!array_key_exists('type', $params)) $params['type'] = 0;
	if (!array_key_exists('title', $params)) $params['title'] = 0;
	if (!array_key_exists('description', $params)) $params['description'] = 0;
	if (!array_key_exists('subtype', $params)) $params['subtype'] = 0;
	if (!array_key_exists('friendly_url', $params)) $params['friendly_url'] = "";
	if (!array_key_exists('sort_order', $params)) $params['sort_order'] = 0;
	if (!array_key_exists('enabled', $params)) $params['enabled'] = 1;
	if (!array_key_exists('parent_id', $params)) $params['parent_id'] = 0;
	if (!array_key_exists('creator_id', $params)) $params['creator_id'] = $loggedin_user->id;
	if (!array_key_exists('logo', $params)) $params['logo'] = false;

	$category_params = null;
	$category_params['owner_id'] = $params['owner_id'];
	$category_params['type'] = $params['type'];
	$category_params['title'] = $params['title'];
	$category_params['description'] = $params['description'];
	$category_params['subtype'] = $params['subtype'];
	$category_params['friendly_url'] = $params['friendly_url'];
	$category_params['sort_order'] = 0;
	$category_params['enabled'] = 0;
	$category_params['parent_id'] = $params['parent_id'];
	$category_params['creator_id'] = $loggedin_user->id;
	$category_params['logo'] = false;

	return response($categoryService->save($category_params));
});