<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/feeds', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	$loggedin_user = loggedin_user();
	if ($loggedin_user->usercurrency)
		$currency_code = $loggedin_user->usercurrency;
	$params = $request->getQueryParams();
	if (!array_key_exists("post_guid", $params)) $params["post_guid"] = false;

	$feed_guid = $params['post_guid'];
	if (!$feed_guid) return response(false);

	$feed_params = null;
	$feed_params[] = [
		'key' => 'guid',
		'value' => "= {$feed_guid}",
		'operation' => ''
	];
	$feed = $select->getFeeds($feed_params,0,1);
	if (!$feed) return response(false);

	if (property_exists($feed, 'linkPreview')) {
		if ($feed->linkPreview) {
			if ($linkPreview) {
				$link_params = null;
				$link_params[] = [
					'key' => 'guid',
					'value' => "= {$feed->linkPreview}",
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
		} else {
			unset($feed->linkPreview);
		}
	}


	if ($feed->poster_guid != $loggedin_user->guid) {
		if (property_exists($loggedin_user, 'blockedusers')) {
			$block_list = json_decode($loggedin_user->blockedusers);
		    if (is_array($block_list) && count($block_list) > 0) {
			    if (in_array($loggedin_user->guid, $block_list)) {
			    	return response([
						'status' => false,
						'error' => 'blocked'
					]);
			    }
		    }
		}
	}

	if ($feed->type != "user") {
		$object_params = null;
		$object_params[] = [
			'key' => 'guid',
			'value' => "= {$feed->owner_guid}",
			'operation' => ''
		];
		$object = $select->getObjects($object_params,0,1);
		if (!$object) return response(false);
		$feed->owner_title = $object->title;
	}

	$like_params = null;
	$like_params[] = [
		'key' => 'subject_id',
		'value' => "= {$feed->guid}",
		'operation' => ''
	];
	$like_params[] = [
		'key' => 'type',
		'value' => "= 'post'",
		'operation' => 'AND'
	];
	$like_params[] = [
		'key' => '*',
		'value' => "count",
		'operation' => 'count'
	];
	$like_params[] = [
		'key' => 'subject_id',
		'value' => "",
		'operation' => 'query_params'
	];
	$like_params[] = [
		'key' => 'subject_id',
		'value' => "",
		'operation' => 'group_by'
	];
	$likes_count = $select->getLikes($like_params,0,99999999);
	$like_params[] = [
		'key' => 'guid',
		'value' => "= {$loggedin_user->guid}",
		'operation' => 'AND'
	];
	$liked_feed = $select->getLikes($like_params,0,99999999);
	$feed->likes = "0";
	if ($likes_count) $feed->likes = (string)$likes_count->count;
	
	$feed->liked = false;
	if ($liked_feed) $feed->liked = true;
	
	$comment_params = null;
	$comment_params[] = [
		'key' => 'type',
		'value' => "= 'comments:post'",
		'operation' => ''
	];
	$comment_params[] = [
		'key' => 'subject_guid',
		'value' => "= {$feed->guid}",
		'operation' => 'AND'
	];
	$comment_params[] = [
		'key' => '*',
		'value' => "count",
		'operation' => 'count'
	];
	$comment_params[] = [
		'key' => 'subject_guid',
		'value' => "",
		'operation' => 'query_params'
	];
	$comment_params[] = [
		'key' => 'subject_guid',
		'value' => "",
		'operation' => 'group_by'
	];
	$comments_count = $select->getAnnotations($comment_params,0, 999999);

	$feed->comments = "0";
	if ($comments_count) $feed->comments = (string) $comments_count->count;

	// mood
	if ($feed->mood) {
		$mood_params = null;
		$mood_params[] = [
			'key' => 'guid',
			'value' => "= {$feed->mood}",
			'operation' => ''
		];
		$mood = $select->getMoods($mood_params,0,1);
		if ($mood) {
			$feed->mood = $mood;
		}
	}

	// users
	$users_guid = [];
	$description = json_decode($feed->description);
	if (!empty($description->friend)) {
		$friends = explode(",", $description->friend);
		$users_guid = array_merge($friends, $users_guid);
	}
	$users_guid = array_unique(array_merge(array($feed->poster_guid), $users_guid));
	$users_result = $users = [];
	if (is_array($users_guid)) {
		$users_guid = implode(',', array_unique($users_guid));
		$user_params = null;
		$user_params[] = [
			'key' => 'guid',
			'value' => "IN ({$users_guid})",
			'operation' => ''
		];
		$users = $select->getUsers($user_params,0,999999);
		if (!$users) return response(false);
		foreach ($users as $key => $user) {
			$users_result[$user->guid] = $user;
		}
	}
	$desc['post'] = $description->post;
	$desc['location'] = $description->location;
	$desc['friend'] = $description->friend;
	$feed->desc = $desc;
	
	return [
		"post" => $feed,
		"users" => $users
	];
	return response(false);
});

$app->post($container['prefix'].'/feeds', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	$feed_params = $mood_params = $feeder_params = $photo_params = $shares = $shares_post = $shares_product = [];
	$loggedin_user = loggedin_user();
	$block_list = 0;
	if (property_exists($loggedin_user, 'blockedusers')) {
		$block_list = json_decode($loggedin_user->blockedusers);
	}
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("feeds_type", $params)) $params["feeds_type"] = "home";
	if (!array_key_exists("offset", $params)) $params["offset"] = 0;
	if (!array_key_exists("limit", $params)) $params["limit"] = 10;
	if (!array_key_exists("owner_guid", $params)) $params["owner_guid"] = $loggedin_user->guid;

	$friends = getFriendsGUID($loggedin_user->guid);

    if ($friends) {
    	$friends_guid = [];
    	array_push($friends_guid, $loggedin_user->guid);
		
    	foreach ($friends as $friend) {
		    if (is_array($block_list) && count($block_list) > 0) {
			    if (in_array($friend, $block_list)) {
			    	unset($friends[$key]);
			    	continue;
			    }
		    }
		    if (!in_array($friend, $friends_guid)) {
				array_push($friends_guid, $friend);
			}
    	}
	    $friends_guid = implode(",", array_unique($friends_guid));
    }

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

	$feeds = $select->getFeeds($feed_params, $offset, $limit);

	if (!$feeds) return false;
	$feeds_guid = $linkPreview = $objects_guid = $users_guid = $mood_guids = [];

	foreach ($feeds as $key => $feed) {
		if (!in_array($feed->guid, $feeds_guid)) {
			array_push($feeds_guid, $feed->guid);
		}
		if (property_exists($feed, 'linkPreview')) {
			if ($feed->linkPreview) unset($feed->linkPreview);
		}
		if ($feed->type != "user") {
			if (!in_array($feed->owner_guid, $objects_guid)) {
				array_push($objects_guid, $feed->owner_guid);
			}
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
				 			$feed_shared = $select->getFeeds($feed_params,0,1);
				 			if (!$feed_shared) continue;
				 			if ($feed_shared) {
				 				array_push($shares_post, $feed_shared);
				 			} else {
				 				unset($feeds[$key]);
				 				continue;
				 			}
				 		}
				 	}
			 		break;
			 	case 'product':
			 		if ($feed->item_guid) {
			 			unset($feeds[$key]);
		 				continue;
			 		}
			 		break;
			 	default:
			 		# code...
			 		break;
			}
		}

		if (property_exists($feed, 'linkPreview') && $feed->linkPreview) {
			if (!in_array($feed->linkPreview, $linkPreview)) {
				array_push($linkPreview, $feed->linkPreview);
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
				if (!in_array($feed_share->linkPreview, $linkPreview)) {
					array_push($linkPreview, $feed_share->linkPreview);
				}
			} else {
				unset($feed_share->linkPreview);
			}

			$shares['posts'][$feed_share->guid] = $feed_share;
		}
	}

	$feeds_guid = implode(',', array_unique($feeds_guid));

	$like_params = null;
	$like_params[] = [
		'key' => 'subject_id',
		'value' => "IN ({$feeds_guid})",
		'operation' => ''
	];
	$like_params[] = [
		'key' => 'type',
		'value' => "= 'post'",
		'operation' => 'AND'
	];
	$like_params[] = [
		'key' => '*',
		'value' => "count",
		'operation' => 'count'
	];
	$like_params[] = [
		'key' => 'subject_id',
		'value' => "",
		'operation' => 'query_params'
	];
	$like_params[] = [
		'key' => 'subject_id',
		'value' => "",
		'operation' => 'group_by'
	];
	$likes_count = $select->getLikes($like_params,0,99999999);
	$like_params[] = [
		'key' => 'guid',
		'value' => "= {$loggedin_user->guid}",
		'operation' => 'AND'
	];
	$liked_feed = $select->getLikes($like_params,0,99999999);

	$comment_params = null;
	$comment_params[] = [
		'key' => 'type',
		'value' => "= 'comments:post'",
		'operation' => ''
	];
	$comment_params[] = [
		'key' => 'subject_guid',
		'value' => "IN ({$feeds_guid})",
		'operation' => 'AND'
	];
	$comment_params[] = [
		'key' => '*',
		'value' => "count",
		'operation' => 'count'
	];
	$comment_params[] = [
		'key' => 'subject_guid',
		'value' => "",
		'operation' => 'query_params'
	];
	$comment_params[] = [
		'key' => 'subject_guid',
		'value' => "",
		'operation' => 'group_by'
	];
	$comments_count = $select->getAnnotations($comment_params,0, 999999);

	if ($objects_guid) {
		$objects_guid = implode(',', array_unique($objects_guid));
		$object_param = null;
		$object_param = [
			'key' => 'guid',
			'value' => "IN ({$objects_guid})",
			'operation' => ''
		];
		$objects = $select->getObjects($object_param,0,99999999);
	}

	if ($linkPreview) {
		$linkPreview = implode(',', array_unique($linkPreview));
		$link_params = null;
		$link_params[] = [
			'key' => 'guid',
			'value' => "= {$linkPreview}",
			'operation' => ''
		];
		$links = $select->getLinkPreview($link_params,0,1);
	}

	foreach ($feeds as $key => $feed) {
		$feed->likes = "0";
		foreach ($likes_count as $like_count) {
			if ($like_count->subject_id == $feed->guid) {
				$feed->likes = (string) $like_count->count;
			}
		}
		$feed->liked = false;
		if ($liked_feed) {
			foreach ($liked_feed as $liked) {
				if ($liked->subject_id == $feed->guid) {
					$feed->liked = true;
				}
			}
		}
		$feed->comments = "0";
		if ($comments_count) {
			foreach ($comments_count as $comment_count) {
				if ($comment_count->subject_guid == $feed->guid) {
					$feed->comments = (string) $comment_count->count;
				}
			}
		}
		if ($feed->type != "user") {
			if ($objects) {
				$flag = false;
				foreach ($objects as $object) {
					if ($object->guid == $feed->owner_guid) {
						$flag = true;
						$feed->owner_title = $object->title;
					}
				}
				if (!$flag) {
					unset($feeds[$key]);
					continue;
				}
			}
		}
		if ($linkPreview) {
			$flag = false;
			foreach ($links as $key => $link) {
				if ($link->guid == $feed->linkPreview) {
					$flag = true;
					$feed->linkPreview = $link;
				}
			}
			if (!$flag) unset($feed->linkPreview);
		}
		$feeds[$key] = $feed;
	}

	if (is_array($shares_post) && count($shares_post) > 0) {
		foreach ($shares_post as $feed_share) {
			if (property_exists($feed_share, 'linkPreview') && $feed_share->linkPreview) {
				if (!in_array($feed_share->linkPreview, $linkPreview)) {
					array_push($linkPreview, $feed_share->linkPreview);
				}
				$flag = false;
				foreach ($links as $key => $link) {
					if ($link->guid == $feed_share->linkPreview) {
						$flag = true;
						$feed_share->linkPreview = $link;
					}
				}
				if (!$flag) unset($feed_share->linkPreview);
			} else {
				unset($feed_share->linkPreview);
			}

			$shares['posts'][$feed_share->guid] = $feed_share;
		}
	}

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

			$users = $select->getUsers($user_params,0,999999,false);
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

$app->put($container['prefix'].'/feeds', function (Request $request, Response $response, array $args) {
	$db = SlimDatabase::getInstance();
	$loggedin_user = loggedin_user();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("content", $params)) $params["content"] = false;
	if (!array_key_exists("friends", $params)) $params["friends"] = false;
	if (!array_key_exists("location", $params)) $params["location"] = false;
	if (!array_key_exists("privacy", $params)) $params["privacy"] = "";
	if (!array_key_exists("images", $params)) $params["images"] = false;
	if (!array_key_exists("mood", $params)) $params["mood"] = "";
	if (!array_key_exists("type", $params)) $params["type"] = "user";
	if (!array_key_exists("owner_guid", $params)) $params["owner_guid"] = $loggedin_user->guid;

	$post     	= $params["content"];
	$friends  	= $params["friends"];
	$location 	= $params["location"];
	$privacy  	= $params["privacy"];
	$images 	= $params["images"];
	$mood 		= $params["mood"];
	$type 		= $params["type"];
	$owner_guid = $params["owner_guid"];

	$path = "users/{$loggedin_user->username}/";
	$fb_params = [
		'mood' => (string)$mood
	];
	insertFirebase($path, $fb_params);

	$post = preg_replace('/\t/', ' ', $post);
	$post = str_replace("\\n\\r", "", $post);
	$wallpost['post'] = htmlspecialchars($post, ENT_QUOTES, 'UTF-8');
	
	//wall tag a friend , GUID issue #566
	if(!empty($friends)) {
		$friend_guids = explode(',', $friends);
		//reset friends guids
		$friends      = array();
		foreach($friend_guids as $guid) {
				if(ossn_user_by_guid($guid)) {
						$friends[] = $guid;
				}
		}
		$wallpost['friend'] = implode(',', $friends);
	}
	if(!empty($location)) {
		$wallpost['location'] = $location;
	}
	//Encode multibyte Unicode characters literally (default is to escape as \uXXXX)
	$this->description = json_encode($wallpost, JSON_UNESCAPED_UNICODE);

	$feed = new stdClass;
	$feed->type = $type;
	$feed->owner_guid = $owner_guid;
	$feed->subtype = 'wall';
	$feed->title = '';
	$feed->description = '';
	
	insertEAV($object, $show_id = false)

	$wall = new OssnWall;
	$wall->type = $type;
	$wall->poster_guid = $loggedin_user->guid;
	$wall->owner_guid = $owner_guid;

	$feed_guid = $wall->Post($post, $friends, $location, $privacy, $images, $mood, "post", true);
	if ($feed_guid) {
		return [
			'status' => true,
			'guid' => $feed_guid
		];
	}
	return false;

});