<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/feeds', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	$feed_params = $mood_params = $poster_params = $photo_params = $shares = $shares_post = $shares_product = [];
	$loggedin_user = loggedin_user();
	$block_list = 0;
	if (property_exists($loggedin_user, 'blockedusers')) {
		$block_list = json_decode($loggedin_user->blockedusers);
	}

	$relation_params = null;
    $relation_params[] = [
    	'key' => 'type',
    	'value' => "= 'friend:request'",
    	'operation' => ''
    ];
    $relation_params[] = [
    	'key' => 'relation_to',
    	'value' => "= {$loggedin_user->guid}",
    	'operation' => 'AND'
    ];
    $relation_params[] = [
    	'key' => 'relation_from',
    	'value' => '',
    	'operation' => 'query_params'
    ];
    
    $relations = $select->getRelationships($relation_params,0,99999999);
    if ($relations) {
    	$relations_from = array_map(create_function('$o', 'return $o->relation_from;'), $relations);
    	$relations_from = implode(",", $relations_from);

	    $relation_params = null;
	    $relation_params[] = [
	    	'key' => 'type',
	    	'value' => "= 'friend:request'",
	    	'operation' => ''
	    ];
	    $relation_params[] = [
	    	'key' => 'relation_from',
	    	'value' => "= {$loggedin_user->guid}",
	    	'operation' => 'AND'
	    ];
	    $relation_params[] = [
	    	'key' => 'relation_to',
	    	'value' => "IN ($relations_from)",
	    	'operation' => 'AND'
	    ];
	    $relation_params[] = [
	    	'key' => 'relation_to',
	    	'value' => '',
	    	'operation' => 'query_params'
	    ];
	    
	    $friends = $select->getRelationships($relation_params,0,99999999);
	    if ($friends) {
	    	$friends_guid = [];
	    	array_push($friends_guid, $loggedin_user->guid);
			
	    	foreach ($friends as $key => $friend) {
			    if (is_array($block_list) && count($block_list) > 0) {
				    if (in_array($friend->relation_to, $block_list)) {
				    	unset($friends[$key]);
				    	continue;
				    }
			    }
			    if (!in_array($friend->relation_to, $friends_guid)) {
					array_push($friends_guid, $friend->relation_to);
				}
	    	}
		    $friends_guid = implode(",", $friends_guid);
	    }
	}

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("feeds_type", $params)) $params["feeds_type"] = "home";
	if (!array_key_exists("offset", $params)) $params["offset"] = 0;
	if (!array_key_exists("limit", $params)) $params["limit"] = 10;
	if (!array_key_exists("owner_guid", $params)) $params["owner_guid"] = $loggedin_user->guid;


	$feeds_type = $params["feeds_type"];
	$offset = (double) $params["offset"];
	$limit = (double) $params["limit"];
	$owner_guid = $params["owner_guid"];
	
	$feed_params[] = [
		'key' => 'time_created',
		'value' => 'DESC',
		'operation' => 'order_by'
	];	

	switch ($feeds_type) {
		case 'home':
			$feed_params[] = [
				'key' => 'poster_guid',
				'value' => "= {$loggedin_user->guid}",
				'operation' => ''
			];
			$feed_params[] = [
				'key' => 'access',
				'value' => "= 1",
				'operation' => 'AND'
			];

			if ($friends) {
				$feed_params[] = [
					'key' => '',
					'value' => "(access = 3 AND poster_guid IN ({$friends_guid}) )",
					'operation' => 'OR'
				];
			}
			$feed_params[] = [
				'key' => 'access',
				'value' => "= 2",
				'operation' => 'OR'
			];
			break;
		case 'user':
			if ($loggedin_user->guid == $owner_guid) {
				$feed_params[] = [
					'key' => '',
					'value' => "(poster_guid = {$loggedin_user->guid} AND access = 1)",
					'operation' => ''
				];
				$feed_params[] = [
					'key' => '',
					'value' => "(poster_guid = {$loggedin_user->guid} AND access = 2)",
					'operation' => 'OR'
				];
				$feed_params[] = [
					'key' => '',
					'value' => "(poster_guid = {$loggedin_user->guid} AND access = 3)",
					'operation' => 'OR'
				];
			} else {
				$feed_params[] = [
					'key' => '',
					'value' => "(poster_guid = {$owner_guid} AND access = 2)",
					'operation' => ''
				];
			}			
			break;
		case 'friends':
			if ($loggedin_user->guid == $owner_guid) {
				$feed_params[] = [
					'key' => '',
					'value' => "(poster_guid = {$loggedin_user->guid} AND access IN (1,2,3))",
					'operation' => ''
				];
			} else {
				$feed_params[] = [
					'key' => '',
					'value' => "(poster_guid = {$loggedin_user->guid} AND access IN (2,3))",
					'operation' => ''
				];
			}
			if ($friends) {
				$feed_params[] = [
					'key' => '',
					'value' => "(access = 3 AND poster_guid IN ({$friends_guid}))",
					'operation' => 'OR'
				];
				$feed_params[] = [
					'key' => '',
					'value' => "(access = 2 AND poster_guid IN ({$friends_guid}))",
					'operation' => 'OR'
				];
			}
			break;
		default:
			$feed_params[] = [
				'key' => 'owner_guid',
				'value' => "= {$owner_guid}",
				'operation' => ''
			];
			if ($owner_guid != $loggedin_user->guid) {
				$feed_params[] = [
					'key' => 'access',
					'value' => "<> 1",
					'operation' => 'AND'
				];
			}
			break;
	}

	if (is_array($block_list) && count($block_list) > 0) {
		$block_list = implode(",", array_unique($block_list));
		$feed_params[] = [
			'key' => 'poster_guid',
			'value' => "NOT IN ({$block_list})",
			'operation' => 'AND'
		];
	}

	$feeds = $select->getFeeds($feed_params, $offset, $limit, true);
	if (!$feeds) return false;
	$mood_guids = [];
	$users_guid = [];

	foreach ($feeds as $key => $feed) {
		if ($feed->linkPreview) unset($feed->linkPreview);
		if ($feed->type != "user") {
			$object_param = null;
			$object_param = [
				'key' => 'guid',
				'value' => "= {$feed->owner_guid}",
				'operation' => ''
			];
			$object = $select->getObjects($object_param,0,1);
			if (!$object) {
				unset($feeds[$key]);
				continue;
			}
			$feed->owner_title = $object->title;
		}

		
		if (property_exists($feed, 'mood_guid') && $feed->mood_guid) {
			array_push($mood_guids, $feed->mood_guid);
		}
		array_push($users_guid, $feed->poster_guid);

		$description = json_decode($feed->description);
		if (property_exists($description, 'friend') && $description->friend) {
			$friends = explode(",", $description->friend);
			$users_guid = array_merge($friends, $users_guid);
		}
		if (property_exists($feed, 'share_type')) {
			switch ($feed->share_type) {
			 	case 'post':
			 		if ($feed->item_type == "post:share:post") {
				 		if ($feed->item_guid) {
				 			$feed_params = null;
				 			$feed_params[] = [
				 				'key' => 'guid',
				 				'value' => "= {$feed->item_guid}",
				 				'operation' => ''
				 			];
				 			$post_shared = $select->getFeeds($feed_params,0,1);
				 			if (!$post_shared) continue;
				 			if ($post_shared) {
				 				array_push($shares_post, $post_shared);
				 			} else {
				 				unset($feeds[$key]);
				 				continue;
				 				// $wall = new OssnWall;
				 				// $wall->deletePost($feed->guid);
				 			}
				 		}
				 	}
			 		break;
			 	case 'product':
			 		if ($feed->item_guid) {
			 			unset($feeds[$key]);
		 				continue;
			 			// $product = ossn_get_object($feed->item_guid);
			 			// if ($product) {
			 			// 	array_push($shares_product, $feed->item_guid);
			 			// }
			 		}
			 		break;
			 	default:
			 		# code...
			 		break;
			}
		}

		if (property_exists($feed, 'linkPreview') && $feed->linkPreview) {
			$link_params = null;
			$link_params[] = [
				'key' => 'guid',
				'value' => "= '{$feed->linkPreview}'",
				'operation' => ''
			];
			$link = $select->getLinkPreview($link_params,0,1);
			if ($link) {
				$feed->linkPreview = $link;
			} else {
				unset($feed->linkPreview);	
			}
		} else {
			unset($feed->linkPreview);
		}
	}
	
	$shares_post = array_unique($shares_post);
	$shares_product = array_unique($shares_product);

	if (is_array($shares_post) && count($shares_post) > 0) {
		foreach ($shares_post as $feed_share) {
			if (property_exists($feed_share, 'mood_guid') && $feed_share->mood_guid) {
				array_push($mood_guids, $feed_share->mood_guid);
			}
			array_push($users_guid, $feed_share->poster_guid);
			$description = json_decode($feed_share->description);
			if (property_exists($description, 'friend') && $description->friend) {
				$friends = explode(",", $description->friend);
				$users_guid = array_merge($friends, $users_guid);
			}

			if (property_exists($feed_share, 'linkPreview') && $feed_share->linkPreview) {
				$link_params = null;
				$link_params[] = [
					'key' => 'guid',
					'value' => "= {$feed_share->linkPreview}",
					'operation' => ''
				];
				$link = $select->getLinkPreview($link_params,0,1);
				if ($link) {
					$feed_share->linkPreview = $link;
				} else {
					unset($feed_share->linkPreview);	
				}
			} else {
				unset($feed_share->linkPreview);
			}

			$shares['posts'][$feed_share->guid] = $feed_share;
		}
	}

	// if (is_array($shares_product) && count($shares_product) > 0) {
	// 	foreach ($shares_product as $guid) {
	// 		$product = ProductsService::getInstance()->changeStructureProductOnView($guid, false, true);
	// 		if (!$product) continue;
	// 		$shares['products'][$guid] = $product;
	// 	}
	// }

	if (is_array($mood_guids) && count($mood_guids) > 0) {
		$mood_guids = implode(",", array_unique($mood_guids));

		$mood_params = null;
		$mood_params[] = [
			'key' => 'guid',
			'value' => "= IN ({$mood_guids})",
			'operation' => ''
		];
		$moods = $select->getMoods($mood_params,0,99999);
		if ($moods) {
			foreach ($moods as $key => $mood) {
				// $mood->mood_icon = market_photo_url($mood->guid, $mood->mood_icon, "mood");
				$return["moods"][$mood->guid] = $mood;
			}
		}
	}

	if (is_array($users_guid) && count($users_guid) > 0) {
		$users_guid = implode(",", array_unique($users_guid));
		if ($users_guid) {
			$user_params = null;
			$user_params[] = [
				'key' => 'guid',
				'value' => "IN ({$users_guid})",
				'operation' => ''
			];

			$users = $select->getUsers($user_params,0,999999,true,false);
			if (!$users) return false;
			foreach ($users as $key => $user) {
				$return["users"][$user->guid] = $user;
			}
		}
	}

	if (!$feeds) return false;
	$return["posts"] = array_values($feeds);

	if ($shares) {
		$return["shares"] = $shares;
	}
	return response($return);
});