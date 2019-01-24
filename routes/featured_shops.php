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
		$shop = $shopService->getShopByType($advertise->target_id, 'id', 0, 999999);
		if (!$shop) continue;
		if ($shop->approved <= 0) continue;
		if ($shop->status != 1) continue;
		$shop->advertise_id = $advertise->id;
		array_push($ads, $shop);
	}
	return response(array_values($ads));
});