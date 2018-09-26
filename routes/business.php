<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/business', function (Request $request, Response $response, array $args) {
	$businessService = BusinessService::getInstance();
	$userService = UserService::getInstance();
	$likeService = LikeService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists("id", $params)) return response(false);
	$page = $businessService->getPageById($params['id']);
	$page->owner = $userService->getUserByType($page->owner_guid,'id',false);
	$page->followed = $likeService->isLiked($loggedin_user->id, $page->id, 'business');
	return response($page);
});

$app->post($container['prefix'].'/business', function (Request $request, Response $response, array $args) {
	$businessService = BusinessService::getInstance();
	$userService = UserService::getInstance();
	$likeService = LikeService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("offset", $params)) $params["offset"] = 0;
	if (!array_key_exists("limit", $params)) $params["limit"] = 10;

	$owner_guid = $loggedin_user->id;
	$offset = (double)$params['offset'];
	$limit = (double)$params['limit'];

	$pages_liked = $likeService->getPagesLiked($loggedin_user->id);
	$pages_liked = array_map(create_function('$o', 'return $o->subject_id;'), $pages_liked);

	$page_params = null;
	$page_params = [
		'key' => 'owner_guid',
		'value' => "= {$loggedin_user->id}",
		'operation' => ''
	];

	if ($pages_liked && count($pages_liked) > 0) {
		$pages_liked = implode(',', $pages_liked);
		$page_params = [
			'key' => 'id',
			'value' => "IN ($pages_liked)",
			'operation' => 'AND'
		];
	}

	$pages = $businessService->getPages($page_params, $offset, $limit);
	if (!$pages) return response(false);
	return response($pages);
});