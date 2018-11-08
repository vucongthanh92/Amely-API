<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/click_ads', function (Request $request, Response $response, array $args) {
	$advertiseService = AdvertiseService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!array_key_exists("advertise_id", $params)) return response(false);

	$advertise = $advertiseService->clickAd($params['advertise_id']);
	if (!$advertise) return response(false);

	return response(true);
});