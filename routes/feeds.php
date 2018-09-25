<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/feeds', function (Request $request, Response $response, array $args) {
	$feedService = FeedService::getInstance();
	$userService = UserService::getInstance();
	$likeService = LikeService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!array_key_exists("id", $params)) return response(false);
	$feed = $feedService->getFeedById($params['id']);
	if (!$feed) return response(false);
	$feed->likes = $feedService->countLike($feed->id);
	$feed->comments = $feedService->countComment($feed->id);
	$feed->liked = $likeService->isLiked($loggedin_user->id, $feed->id, 'feed');

	$owner = new stdClass;
	$owner->id = $feed->owner_guid;
	$owner->type = $feed->type;
	$owner->title = $feed->title;
	$feed->owner = $owner;

	if ($feed->poster_guid == $loggedin_user->id) {
		$poster = new stdClass;
		$poster = $loggedin_user;
	} else {
		$poster = $userService->getUserByType($feed->poster_guid, 'id', false);
	}
	$feed->poster = $poster;

	if ($feed->item_type == 'feed') {
		$feed_share = $feedService->getFeedById($feed->item_guid);
		if (!$feed_share) return response(false);
		$feed_share->likes = $feedService->countLike($feed_share->id);
		$feed_share->comments = $feedService->countComment($feed_share->id);
		$feed_share->liked = $likeService->isLiked($loggedin_user->id, $feed_share->id, 'feed');

		$owner = new stdClass;
		$owner->id = $feed_share->owner_guid;
		$owner->type = $feed_share->type;
		$owner->title = $feed_share->title;
		$feed_share->owner = $owner;

		if ($feed_share->poster_guid == $loggedin_user->id) {
			$poster = new stdClass;
			$poster = $loggedin_user;
		} else {
			$poster = $userService->getUserByType($feed_share->poster_guid, 'id', false);
		}
		$feed_share->poster = $poster;
		$feed->share = $feed_share;
	}

	return response($feed);
});

$app->post($container['prefix'].'/feeds', function (Request $request, Response $response, array $args) {

	$feedService = FeedService::getInstance();
	$userService = UserService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];

	if (!array_key_exists("feeds_type", $params)) $params["feeds_type"] = "home";
	if (!array_key_exists("offset", $params)) $params["offset"] = 0;
	if (!array_key_exists("limit", $params)) $params["limit"] = 10;
	if (!array_key_exists("owners", $params)) $params["owners"] = false;
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
				'key' => '',
				'value' => "(poster_guid = {$loggedin_user->guid} AND privacy IN (0,1,2))",
				'operation' => ''
			];
			if ($params['owners']) {
				$owners = implode(',', array_unique($params['owners']));
				$feed_params[] = [
					'key' => '',
					'value' => "(privacy <> 0 AND poster_guid IN ({$owners}) )",
					'operation' => 'OR'
				];
			}
			break;
		case 'user':
			if ($loggedin_user->guid == $owner_guid) {
				$feed_params[] = [
					'key' => '',
					'value' => "(poster_guid = {$loggedin_user->guid} AND privacy IN (0,1,2))",
					'operation' => ''
				];
			} else {
				$feed_params[] = [
					'key' => '',
					'value' => "(poster_guid = {$owner_guid} AND privacy IN (1,2))",
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
				'key' => 'owner_guid',
				'value' => "= {$owner_guid}",
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
				'key' => 'owner_guid',
				'value' => "= {$owner_guid}",
				'operation' => 'AND'
			];
			break;
		case 'business'
			$feed_params[] = [
				'key' => 'type',
				'value' => "= 'business'",
				'operation' => ''
			];
			$feed_params[] = [
				'key' => 'owner_guid',
				'value' => "= {$owner_guid}",
				'operation' => 'AND'
			];
			break;
		default:
			return response(false);
			break;
	}
	$feeds = $feedService->getFeeds($feed_params, $offset, $limit);
	if (!$feeds) return false;
	$feeds_users = $feeds_guid = $shares = $feeds_share_guid = $users_guid = $mood_guids = [];

	foreach ($feeds as $key => $feed) {
		if (!in_array($feed->guid, $feeds_guid)) {
			array_push($feeds_guid, $feed->guid);
		}
		if ($feed->type == 'user') {
			if (!in_array($feed->owner_guid, $users_guid)) {
				array_push($users_guid, $feed->owner_guid);
			}
		}
		if (!in_array($feed->owner_guid, $users_guid)) {
			array_push($users_guid, $feed->poster_guid);
		}
		if ($feed->item_type == 'feed') {
			if (!in_array($feed->item_guid, $feeds_share_guid)) {
				array_push($feeds_share_guid, $feed->item_guid);
			}
		}
	}

	if ($feeds_share_guid && count($feeds_share_guid) > 0) {
		$feeds_share_guid = implode(',', $feeds_share_guid);
		$feed_params = null;
		$feed_params[] = [
			'key' => 'id',
			'value' => "IN ({$feeds_share_guid})",
			'operation' => ''
		];
		$feeds_share = $feedService->getFeeds($feed_params, 0, 999999999);
		if (!$feeds_share) return response(false);
		foreach ($feeds_share as $key => $feed_share) {
			if (!in_array($feed_share->guid, $feeds_guid)) {
				array_push($feeds_guid, $feed_share->guid);
			}
			if ($feed_share->type == 'user') {
				if (!in_array($feed_share->owner_guid, $users_guid)) {
					array_push($users_guid, $feed_share->owner_guid);
				}
			}
			if (!in_array($feed_share->owner_guid, $users_guid)) {
				array_push($users_guid, $feed_share->poster_guid);
			}

			$owner = new stdClass;
			$owner->id = $feed_share->owner_guid;
			$owner->type = $feed_share->type;
			$owner->title = $feed_share->title;
			$feed_share->owner = $owner;
			$shares[$feed_share->id] = $feed_share;
		}
	}

	$feeds_guid = array_unique($feeds_guid);
	$feeds_guid = implode(',', array_unique($feeds_guid));

	$feeds_likes = $feedService->getFeedsCountLike($feeds_guid);
	$feeds_liked = $feedService->getFeedsLiked($loggedin_user->id, $feeds_guid);
	$feeds_comments = $feedService->getFeedsCountComment($feeds_guid);


	if (is_array($users_guid) && count($users_guid) > 0) {
		$users_guid = implode(",", array_unique($users_guid));
		$users = $userService->getUsersByType($users_guid, 'id', false);
		if (!$users) return response(false);
		foreach ($users as $key => $user) {
			$feeds_users[$user->id] = $user;
		}
	}

	foreach ($feeds as $key => $feed) {
		$feed->likes = 0;
		if ($feeds_likes) {
			foreach ($feeds_likes as $feed_likes) {
				if ($feed_likes->subject_id == $feed->guid) {
					$feed->likes = $feed_likes->count;
				}
			}
		}
		$feed->liked = false;
		if ($liked_feed) {
			foreach ($feeds_liked as $feed_liked) {
				if ($feed_liked->subject_id == $feed->guid) {
					$feed->liked = true;
				}
			}
		}
		$feed->comments = 0;
		if ($feeds_comments) {
			foreach ($feeds_comments as $feed_comments) {
				if ($feed_comments->subject_guid == $feed->guid) {
					$feed->comments = $feed_comments->count;
				}
			}
		}

		$owner = new stdClass;
		$owner->id = $feed->owner_guid;
		$owner->type = $feed->type;
		$owner->title = $feed->title;
		$feed->owner = $owner;
		$feed->poster = $feeds_users[$feed->poster_guid];
		if ($feed->item_type == 'feed') {
			$feed->share = $shares[$feed->item_guid];
			$feed->share->poster = $feeds_users[$feed->share->poster_guid];
		}
		
		$feeds[$key] = $feed;
	}
	
	return response($feeds);
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
	if($friends) {
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
	
	return insertEAV($object, $show_id = false);
});