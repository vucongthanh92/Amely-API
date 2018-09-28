<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/categories', function (Request $request, Response $response, array $args) {
	$categoryService = CategoryService::getInstance();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('offset', $params)) $params['offset'] = 0;
	if (!array_key_exists('limit', $params)) $params['limit'] = 10;
	if (!array_key_exists('shop_id', $params)) $params['shop_id'] = false;
	// type ['voucher','ticket','market']
	if (!array_key_exists('type', $params)) $params['type'] = 0;

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
		switch ($type) {
			case 'voucher':
				$category_params[] = [
					'key' => 'subtype',
					'value' => "= 'voucher'",
					'operation' => ''
				];
				break;
			case 'ticket':
				$category_params[] = [
					'key' => 'subtype',
					'value' => "= 'ticket'",
					'operation' => ''
				];
				break;
			default:
				$category_params[] = [
					'key' => 'subtype',
					'value' => "= 'market'",
					'operation' => ''
				];
				break;
		}
	}

	$categories = $categoryService->getCategories($category_params, $offset, $limit);
	if (!$categories) return response(false);
	return response($categories);
});