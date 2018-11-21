<?php
use Slim\Http\Request;
use Slim\Http\Response;

// thong tin he thong
$app->get($container['administrator'].'/systems', function (Request $request, Response $response, array $args) {
	$siteSettingService = SiteSettingService::getInstance();
	$loggedin_user = loggedin_user();
	$settings = $siteSettingService->getSiteSettings(null, 0, 99999999);
	return response($settings);
});

// them hoac chinh sua thong tin he thong
$app->post($container['administrator'].'/systems', function (Request $request, Response $response, array $args) {
	$siteSettingService = SiteSettingService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('id', $params)) 				$params['id'] = false;
	if (!array_key_exists('title', $params)) 			$params['title'] = 0;
	if (!array_key_exists('name', $params)) 		$params['name'] = 0;
	if (!array_key_exists('value', $params)) 				$params['value'] = 0;
	if ($loggedin_user->type != 'admin') return response(false);

	$system_data = null;
	if ($params['id']) {
		$system_data['title'] = $params['id'];	
	}
	$system_data['title'] = $params['title'];
	$system_data['name'] = $params['name'];
	$system_data['value'] = $params['value'];

	return response($siteSettingService->save($system_data));
});