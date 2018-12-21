<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/banner', function (Request $request, Response $response, array $args) {
	global $settings;

	$advertiseService = AdvertiseService::getInstance();
	$userService = UserService::getInstance();
	$groupService = GroupService::getInstance();
	$shopService = ShopService::getInstance();
	$productService = ProductService::getInstance();

	$loggedin_user = loggedin_user();
	$advertises = $advertiseService->getAdvertiseBanner();
	if (!$advertises) return response(false);
	foreach ($advertises as $key => $advertise) {
		$parse = parse_url($advertise->link);
		$url = preg_replace('/^www\./i', '', $parse['host']);
		if ($url == "amely.com") {
			$path = explode("/", $parse['path']);
			$url_type = $path[1];
			$friendly_url = $path[2];
			switch ($url_type) {
				case 'u':
					$user = $userService->getUserByType($friendly_url, 'username', false);
					$banners[$key]['type'] = "user";
					$banners[$key]['user_id'] = $user->id;
					break;
				case 'g':
					$banners[$key]['type'] = "group";
					$group = $groupService->getGroupByType($friendly_url, 'id');
					$matches = array();
					preg_match_all('/.*?-(\\d+)$/i', $friendly_url, $matches);
					if (count($matches) >= 2) {
						$banners[$key]['group_id'] = $matches[1][0];
					}
					break;
				case 'p':
					$product = $productService->getProductByType($friendly_url, 'friendly_url');
					$banners[$key]['type'] = "product";
					$banners[$key]['product_id'] = $product->id;
					break;
				case 's':
					$shop = $shopService->getShopByType($friendly_url, 'friendly_url', false);
					$banners[$key]['type'] = "shop";
					$banners[$key]['shop_id'] = $shop->id;
					$banners[$key]['shop_owner_id'] = $shop->owner_id;
					break;
				case 'event':
					$banners[$key]['type'] = "event";
					$banners[$key]['event_id'] = $path[3];
					break;
				case 'page':
					$banners[$key]['type'] = "page";
					$banners[$key]['page_id'] = $path[3];
					break;
				default:
					break;

			}
		}
		$banners[$key]['banner_url'] = $advertise->image;
		$banners[$key]['link'] = $advertise->link;
		$banners[$key]['id'] = $advertise->id;
    }
	return response(array_values($banners));

});