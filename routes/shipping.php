<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/shipping', function (Request $request, Response $response, array $args) {
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('shipping_method', $params)) $params['shipping_method'] = false;
	if (!array_key_exists('pick_province', $params)) $params['pick_province'] = false;
	if (!array_key_exists('pick_district', $params)) $params['pick_district'] = false;
	if (!array_key_exists('province', $params)) $params['province'] = false;
	if (!array_key_exists('district', $params)) $params['district'] = false;
	if (!array_key_exists('address', $params)) $params['address'] = false;
	if (!array_key_exists('weight', $params)) $params['weight'] = false;
	if (!array_key_exists('total', $params)) $params['total'] = false;

	$data = [];
	$data['pick_province'] = $params['pick_province'];
	$data['pick_district'] = $params['pick_district'];
	$data['province'] = $params['province'];
	$data['district'] = $params['district'];
	$data['address'] = $params['address'];
	$data['weight'] = $params['weight'];
	$data['total'] = $params['total'];
});