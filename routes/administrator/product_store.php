<?php
use Slim\Http\Request;
use Slim\Http\Response;

// thong tin he thong
$app->post($container['administrator'].'/product_store', function (Request $request, Response $response, array $args) {
	// $siteSettingService = SiteSettingService::getInstance();
	// $loggedin_user = loggedin_user();
	// $settings = $siteSettingService->getSiteSettings(null, 0, 99999999);
	// return response($settings);

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('product_id', $params)) 	return response(false);
	if (!array_key_exists('store_id', $params)) return response(false);
	if (!array_key_exists('quantity', $params)) return response(false);

	$productStoreService = ProductStoreService::getInstance();

	


});