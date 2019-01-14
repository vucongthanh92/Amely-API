<?php
use Slim\Http\Request;
use Slim\Http\Response;

// xem chi tiet quang cao
$app->post($container['administrator'].'/services', function (Request $request, Response $response, array $args) {
	$permissionService = PermissionService::getInstance();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('rule_id', $params)) $params['rule_id'] = false;
	if (!array_key_exists('path', $params)) $params['path'] = false;
	if (!array_key_exists('method', $params)) $params['method'] = false;

	if ($params['rule_id'] && $params['path'] && $params['method']) {
		$check_permission = $permissionService->checkPermission($params['rule_id'], $params['path'], $params['method']);

		if ($check_permission) return response(true);
		return response(false);
	}
	return response(false);

})->setName('services');