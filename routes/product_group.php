<?php

use Slim\Http\Request;
use Slim\Http\Response;


$app->get($container['prefix'].'/product_group', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();

	$product_group_params = null;
	$product_group_params[] = [
		'key' => 'time_created',
		'value' => 'DESC',
		'operation' => 'order_by'
	];
	$product_groups = $select->getProductGroup($product_group_params,0,999999999);

	return response($product_groups);
});
