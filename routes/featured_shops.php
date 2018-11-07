<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/featured_shops', function (Request $request, Response $response, array $args) {
	$advertiseService = AdvertiseService::getInstance();
	$shopService = ShopService::getInstance();
	$loggedin_user = loggedin_user();
	$advertises = $advertiseService->getAdvertiseShop();
	if (!$advertises) return response(false);
	$ads = [];
	foreach ($advertises as $key => $advertise) {
		$ads[$advertise->id] = $advertise->target_id;
	}
	$shops_id = array_unique(array_values($ads));
	$shops_id = implode(',', $shops_id);
	if (!$shops_id) return response(false);
	$shops = $shopService->getShopsByType($shops_id, 'id', 0, 999999);

	foreach ($shops as $key => $shop) {
		foreach ($ads as $kad => $ad) {
			if ($ad == $shop->id) {
				$shop->advertise_id = $ads[$kad];
				$shops[$key] = $shop;
			}
		}
	}
	return response(array_values($shops));

});