<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/banner', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	$loggedin_user = loggedin_user();
	$shops_block = 0;
	if (property_exists($loggedin_user, 'blockedusers')) {
		if ($loggedin_user->blockedusers) {
			$block_list = json_decode($loggedin_user->blockedusers);
			$block_users = implode(',', $block_list);
			$shop_params = null;
			$shop_params[] = [
				'key' => 'owner_guid',
				'value' => "IN ({$block_users})",
				'operation' => ''
			];
			$shops = $select->getShops($shop_params,0,999999);
			$shops_block = array_map(create_function('$o', 'return $o->guid;'), $shops);
		}
	}

	$ads_params = null;
	$ads_params = conditionAds();
	$ads_params[] = [
		'key' => 'advertise_type',
		'value' => "= 'banner'",
		'operation' => 'AND'
	];
	if ($shops_block) {
        $ads_params[] = [
        	'key' => 'owner_guid',
        	'value' => "NOT IN ({$shop_block})",
        	'operation' => 'AND'
        ];
    }
    $ads_params[] = [
    	'key' => 'cpc',
    	'value' => "DESC",
    	'operation' => 'order_by'
    ];
	$ads = $select->getAdvertisements($ads_params, 0, 6);
	if (!$ads) return response(false);
	$final_ads = [];

    foreach ($ads as $advertise) {
        $advertise->balance = $advertise->budget - $advertise->amount;
        $final_ads[$advertise->cpc][] = $advertise;
    }
    if (count($final_ads) > 1) {
    	$final_ads = compareAds($final_ads);
    }
    $banners = [];

    foreach ($final_ads as $advertises) {
        foreach ($advertises as $key => $advertise) {
			$parse = parse_url($advertise->link);
			$url = preg_replace('/^www\./i', '', $parse['host']);
			if ($url == DOMAIN_NAME) {
				$path = explode("/", $parse['path']);
				$url_type = $path[1];
				switch ($url_type) {
					case 'u':
						$user_param = null;
						$user_param[] = [
							'key' => 'username',
							'value' => "= {$path[2]}",
							'operation' => ''
						];
						$user = $select->getUsers($user_param,0,1,true,false);
						$banners[$key]['type'] = "user";
						$banners[$key]['user_guid'] = $user->guid;
						break;
					case 'g':
						$banners[$key]['type'] = "group";
						$matches = array();
						preg_match_all('/.*?-(\\d+)$/i', $path[2], $matches);
						if (count($matches) >= 2) {
							$group_guid = $matches[1][0];
							$banners[$key]['group_guid'] = $group_guid;
						}
						break;
					case 's':
						if ($path[3]) {
							$banners[$key]['type'] = "product";
							$matches = array();
							preg_match_all('/.*?-(\\d+)$/i', $path[3], $matches);
							if (count($matches) >= 2) {
								$product_guid = $matches[1][0];
								$banners[$key]['product_guid'] = $product_guid;
							}
						} else {
							$friendly_url = $path[2];
							$shop_params = null;
							$shop_params[] = [
								'key' => 'friendly_url',
								'value' => "= '{$friendly_url}'",
								'operation' => ''
							];
							$shop = $select->getShops($shop_params,0,1);
							$banners[$key]['type'] = "shop";
							$banners[$key]['shop_guid'] = $shop->guid;
							$banners[$key]['shop_owner_guid'] = $shop->owner_guid;
						}
						break;
					case 'event':
						$event_guid = $path[3];
						$banners[$key]['type'] = "event";
						$banners[$key]['event_guid'] = $event_guid;
						break;
					case 'page':
						$page_guid = $path[3];
						$banners[$key]['type'] = "page";
						$banners[$key]['page_guid'] = $page_guid;
						break;
					default:
						break;

				}
			}
			$banners[$key]['banner_url'] = $image_url;
			$banners[$key]['link'] = $advertise->link;
			$banners[$key]['guid'] = $advertise->guid;
        }
		
    }
    return response($banners);
});