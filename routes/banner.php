<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/banner', function (Request $request, Response $response, array $args) {
	global $settings;

	$advertiseService = AdvertiseService::getInstance();
	$userService = UserService::getInstance();
	$groupService = GroupService::getInstance();
	$shopService = ShopService::getInstance();

	$loggedin_user = loggedin_user();
	$advertises = $advertiseService->getAdvertiseBanner();
	if (!$advertises) return response(false);
	foreach ($advertises as $key => $advertise) {
		$parse = parse_url($advertise->link);
		$url = preg_replace('/^www\./i', '', $parse['host']);
		if ($url == $settings['domain']) {
			$path = explode("/", $parse['path']);
			$url_type = $path[1];
			switch ($url_type) {
				case 'u':
					$user = $userService->getUserByType($path[2], 'username', false);
					$banners[$key]['type'] = "user";
					$banners[$key]['user_id'] = $user->id;
					break;
				case 'g':
					$banners[$key]['type'] = "group";
					$matches = array();
					preg_match_all('/.*?-(\\d+)$/i', $path[2], $matches);
					if (count($matches) >= 2) {
						$banners[$key]['group_id'] = $matches[1][0];
					}
					break;
				case 's':
					if ($path[3]) {
						$banners[$key]['type'] = "product";
						$matches = array();
						preg_match_all('/.*?-(\\d+)$/i', $path[3], $matches);
						if (count($matches) >= 2) {
							$banners[$key]['product_id'] = $matches[1][0];
						}
					} else {
						$friendly_url = $path[2];
						$shop = $shopService->getShopByType($friendly_url, 'friendly_url', false);
						$banners[$key]['type'] = "shop";
						$banners[$key]['shop_guid'] = $shop->id;
						$banners[$key]['shop_owner_id'] = $shop->owner_id;
					}
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
		$banners[$key]['guid'] = $advertise->id;
    }
	return response(array_values($advertises));

});