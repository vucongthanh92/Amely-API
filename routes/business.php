<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/business', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	return response(false);
});

$app->post($container['prefix'].'/business', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("offset", $params)) $params["offset"] = 0;
	if (!array_key_exists("limit", $params)) $params["limit"] = 0;
	if (!array_key_exists("owner_guid", $params)) $params["owner_guid"] = $loggedin_user->guid;
	$owner_guid = $params["owner_guid"];
	$offset = (double)$params['offset'];
	$offset = (double)$params['limit'];
	if (!is_numeric($owner_guid)) $owner_guid = $loggedin_user->guid;
	if (property_exists($loggedin_user, 'blockedusers')) {
    	$block_list = json_decode($loggedin_user->blockedusers);
    	if (is_array($block_list) && count($block_list) > 0) {
			if (in_array($owner_guid, $block_list)) {
		    	return response([
					"status"  => false,
					"error"   => "User is blocked"
				]);
		    }
		}
	}

	$pages_guid = $pages_liked = [];

	$pages_guid = getPagesGUID($owner_guid);
	if (!$pages_guid) return response(false);

	$like_params = null;
	$like_params[] = [
		'key' => 'guid',
		'value' => "= {$loggedin_user->guid}",
		'operation' => ''
	];
	$like_params[] = [
		'key' => 'type',
		'value' => "= 'business:page'",
		'operation' => 'AND'
	];
	$pages_liked = $select->getLikes($like_params, 0, 99999999);

	if ($pages_liked) {
		$pages_liked = array_map(create_function('$o', 'return $o->subject_id;'), $pages_liked);
	}

	$pages_guid = implode(',', array_unique(array_merge($pages_guid, $pages_liked)));

	if (!$pages_guid) return response(false);

	$page_params = null;
	$page_params[] = [
		'key' => 'guid',
		'value' => "IN ({$pages_guid})",
		'operation' => ''
	];
	$pages = $select->getPages($page_params, 0, 99999999);
	if (!$pages) return response(false);
	$owners = [];
	foreach ($pages as $key => $page) {
		if (!in_array($page->owner_guid, $owners)) {
			array_push($owners, $page->owner_guid);
		}
		$page->followed = false;
		if ($pages_liked) {
			if (in_array($page->guid, $pages_liked)) {
				$page->followed = true;
			}
		}
		$pages[$key] = $page;
	}
	
	$users_result = [];
	$owners = implode(',', array_unique($owners));
	$user_params = null;
	$user_params[] = [
		'key' => 'guid',
		'value' => "IN ({$owners})",
		'operation' => ''
	];
	$users = $select->getUsers($user_params,0,9999999,false);
	if (!$users) return response(false);
	foreach ($users as $key => $user) {
		$users_result[$user->guid] = $user;
	}
	
	return [
		'pages' => array_values($pages),
		'users' => $users_result
	];
});