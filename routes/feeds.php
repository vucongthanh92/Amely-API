<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/feeds', function (Request $request, Response $response, array $args) {
	$feedService = FeedService::getInstance();
	$userService = UserService::getInstance();
	$likeService = LikeService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!array_key_exists("feed_id", $params)) return response(false);
	$feed = $feedService->getFeedById($params['feed_id']);
	if (!$feed) return response(false);
	$feed->likes = $feedService->countLike($feed->id);
	$feed->comments = $feedService->countComment($feed->id);
	$feed->liked = $likeService->isLiked($loggedin_user->id, $feed->id, 'feed');

	if ($feed->tag) {
		$tags = $userService->getUsersByType($feed->tag, 'id', false);
		if ($tags && count($tags) > 0) {
			$feed->tags = array_values($tags);
		}
	}

	$owner = new stdClass;
	$owner->id = $feed->owner_id;
	$owner->type = $feed->type;
	$owner->title = $feed->title;
	$feed->owner = $owner;

	if ($feed->poster_id == $loggedin_user->id) {
		$poster = new stdClass;
		$poster = $loggedin_user;
	} else {
		$poster = $userService->getUserByType($feed->poster_id, 'id', false);
	}
	$feed->poster = $poster;

	return response($feed);
});

$app->post($container['prefix'].'/feeds', function (Request $request, Response $response, array $args) {

	$feedService = FeedService::getInstance();
	$userService = UserService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];

	if (!array_key_exists("type", $params)) $params["type"] = "home";
	if (!array_key_exists("offset", $params)) $params["offset"] = 0;
	if (!array_key_exists("limit", $params)) $params["limit"] = 10;
	if (!array_key_exists("owners", $params)) $params["owners"] = false;
	if (!array_key_exists("owner_id", $params)) $params["owner_id"] = $loggedin_user->id;

	$type = $params["type"];
	$offset = (double) $params["offset"];
	$limit = (double) $params["limit"];
	$owner_id = $params["owner_id"];
	
	$feed_params[] = [
		'key' => 'time_created',
		'value' => 'DESC',
		'operation' => 'order_by'
	];	

	switch ($type) {
		case 'home':
			$feed_params[] = [
				'key' => '',
				'value' => "(poster_id = {$loggedin_user->id} AND privacy IN (0,1,2))",
				'operation' => ''
			];
			if ($params['owners'] && count($params['owners']) > 0) {
				$owners = implode(',', array_unique($params['owners']));
				$feed_params[] = [
					'key' => '',
					'value' => "(privacy <> 0 AND poster_id IN ({$owners}) )",
					'operation' => 'OR'
				];
			}
			break;
		case 'user':
			if ($loggedin_user->id == $owner_id) {
				$feed_params[] = [
					'key' => '',
					'value' => "(poster_id = {$loggedin_user->id} AND privacy IN (0,1,2))",
					'operation' => ''
				];
			} else {
				$feed_params[] = [
					'key' => '',
					'value' => "(poster_id = {$owner_id} AND privacy IN (1,2))",
					'operation' => ''
				];
			}			
			break;
		case 'group':
			$feed_params[] = [
				'key' => 'type',
				'value' => "= 'group'",
				'operation' => ''
			];
			$feed_params[] = [
				'key' => 'owner_id',
				'value' => "= {$owner_id}",
				'operation' => 'AND'
			];
			break;
		case 'event':
			$feed_params[] = [
				'key' => 'type',
				'value' => "= 'event'",
				'operation' => ''
			];
			$feed_params[] = [
				'key' => 'owner_id',
				'value' => "= {$owner_id}",
				'operation' => 'AND'
			];
			break;
		case 'business':
			$feed_params[] = [
				'key' => 'type',
				'value' => "= 'business'",
				'operation' => ''
			];
			$feed_params[] = [
				'key' => 'owner_id',
				'value' => "= {$owner_id}",
				'operation' => 'AND'
			];
			break;
		default:
			return response(false);
			break;
	}
	$feeds = $feedService->getFeeds($feed_params, $offset, $limit);
	if (!$feeds) return response(false);
	$feeds_users = $feeds_id  = $users_id = $mood_ids = [];

	foreach ($feeds as $key => $feed) {
		if (!in_array($feed->id, $feeds_id)) {
			array_push($feeds_id, $feed->id);
		}
		if (!in_array($feed->poster_id, $users_id)) {
			array_push($users_id, $feed->poster_id);
		}
		if ($feed->tag) {
			$tag = explode(',', $feed->tag);
			$users_id = array_merge((array)$users_id, (array)$tag);
		}
	}

	$feeds_id = array_unique($feeds_id);
	$feeds_id = implode(',', array_unique($feeds_id));

	$feeds_likes = $feedService->getFeedsCountLike($feeds_id);
	$feeds_liked = $feedService->getFeedsLiked($loggedin_user->id, $feeds_id);
	$feeds_comments = $feedService->getFeedsCountComment($feeds_id);

	if (is_array($users_id) && count($users_id) > 0) {
		$users_id = implode(",", array_unique($users_id));
		$users = $userService->getUsersByType($users_id, 'id', false);
		if (!$users) return response(false);
		foreach ($users as $key => $user) {
			$feeds_users[$user->id] = $user;
		}
	}

	foreach ($feeds as $key => $feed) {
		$feed->likes = 0;
		if ($feeds_likes) {
			foreach ($feeds_likes as $feed_likes) {
				if ($feed_likes->subject_id == $feed->id) {
					$feed->likes = $feed_likes->count;
				}
			}
		}
		$feed->liked = false;
		if ($feeds_liked) {
			foreach ($feeds_liked as $feed_liked) {
				if ($feed_liked->subject_id == $feed->id) {
					$feed->liked = true;
				}
			}
		}
		$feed->comments = 0;
		if ($feeds_comments) {
			foreach ($feeds_comments as $feed_comments) {
				if ($feed_comments->subject_id == $feed->id) {
					$feed->comments = $feed_comments->count;
				}
			}
		}

		if ($feed->tag) {
			$tags = [];
			$list_tag = explode(',', $feed->tag);
			foreach ($list_tag as $tag) {
				if (is_numeric($tag)) {
					if ($feeds_users[$tag]) {
						array_push($tags, $feeds_users[$tag]);
					}
				}
			}
			if ($tags && count($tags) > 0) {
				$feed->tags = array_values($tags);
			}
		}

		$owner = new stdClass;
		$owner->id = $feed->owner_id;
		$owner->type = $feed->type;
		$owner->title = $feed->title;
		$feed->owner = $owner;
		$feed->poster = $feeds_users[$feed->poster_id];
		
		$feeds[$key] = $feed;
	}
	
	return response($feeds);
});

$app->put($container['prefix'].'/feeds', function (Request $request, Response $response, array $args) {
	$services = Services::getInstance();
	$loggedin_user = loggedin_user();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('owner_id', $params)) $params['owner_id'] = $loggedin_user->id;
	if (!array_key_exists('owner_type', $params)) $params['owner_type'] = 'user';
	if (!array_key_exists('owner_title', $params)) $params['owner_title'] = $loggedin_user->fullname;
	if (!array_key_exists('description', $params)) $params['description'] = false;
	if (!array_key_exists('location', $params)) $params['location'] = false;
	if (!array_key_exists('tag', $params)) $params['tag'] = false;
	if (!array_key_exists('mood_id', $params)) $params['mood_id'] = false;
	if (!array_key_exists('privacy', $params)) $params['privacy'] = 0;
	if (!array_key_exists('images', $params)) $params['images'] = false;
	if (!array_key_exists('item_type', $params)) $params['item_type'] = false;
	if (!array_key_exists('item_id', $params)) $params['item_id'] = false;

	$owner_id 		=  $params['owner_id'];
	$type 			=  $params['owner_type'];
	$title 			=  $params['owner_title'];
	$description 	=  $params['description'];
	$location 		=  $params['location'];
	$tag 			=  $params['tag'];
	$mood_id 		=  $params['mood_id'];
	$poster_id 		=  $loggedin_user->id;
	$privacy 		=  $params['privacy'];
	$images 		=  $params['images'];
	$item_type 		=  $params['item_type'];
	$item_id 		=  $params['item_id'];

	$description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');

	// check rule of user when post feed
	// switch ($type) {
	// 	case 'group':
			
	// 		break;
		
	// 	default:
	// 		# code...
	// 		break;
	// }
	$feed = new Feed();
	$feed->data->owner_id = $owner_id;
	$feed->data->type 		= $type;
	$feed->data->title 		= $title;
	$feed->data->description = $description;
	if ($location) {
		$feed->data->location = $location;
	}
	if ($tag && count($tag) > 0) {
		$tag = implode(',', $tag);
		$feed->data->tag = $tag;
	}
	if ($mood_id) {
		$feed->data->mood_id = $mood_id;
	}
	$feed->data->poster_id = $poster_id;
	$feed->data->privacy = $privacy;
	if ($images) {
		$images = implode(',', $images);
		$feed->data->images = $images;
	}
	if ($item_type && $item_id) {
		$feed->data->item_type = $item_type;
		$feed->data->item_id = $item_id;	
	}

	$id = $feed->insert(true);
	if ($id) {
		if ($images) {
			$obj = new stdClass;
			$obj->image_type = 'images';
			$obj->images = $params['images'];
			$obj->owner_id = $id;
			$obj->owner_type = 'feed';
			$services->connectServer("uploads", $obj);
		}
		return response($id);
	}
	return response(false);

});

$app->delete($container['prefix'].'/feeds', function (Request $request, Response $response, array $args) {
	$feedService = FeedService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!array_key_exists('feed_id', $params)) $params['feed_id'] = false;
	if (!$params['feed_id']) return response(false);
	$feed = $feedService->getFeedById($params['feed_id']);
	if (!$feed) return response(false);
	if ($feed->poster_id != $loggedin_user->id) return response(false);
	$feed = object_cast("Feed", $feed);
	$feed->where = "id = '{$feed->id}'";
	// if (!$feed->item_id) {
	// 	$feed_params = null;
	// 	$feed_params[] = [
	// 		'key' => 'item_id',
	// 		'value' => "= {$feed->id}",
	// 		'operation' => ''
	// 	];
	// 	$feed_params[] = [
	// 		'key' => 'item_type',
	// 		'value' => "= 'feed'",
	// 		'operation' => 'AND'
	// 	];
	// 	$feeds_share = $feedService->getFeeds($feed_params,0,9999999);
	// 	if ($feeds_share) {
	// 		foreach ($feeds_share as $key => $feed_share) {
	// 			$feed_share = object_cast("Feed", $feed_share);
	// 			$feed_share->where = "id = '{$feed_share->id}'";
	// 			$feed_share->delete();
	// 		}
	// 	}
	// }

	return response($feed->delete());
});