<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->put($container['prefix'].'/likes', function (Request $request, Response $response, array $args) {
	$notificationService = NotificationService::getInstance();
	$likeService = LikeService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('subject_id', $params)) return response(false);
	if (!array_key_exists('type', $params)) return response(false);

	$from = $loggedin_user->id;
	$subject_id = $params['subject_id'];
	$to = $subject_id;
	$type = $params['type'];
	if (!in_array($type, ['feed', 'business', 'shop'])) return response(false);
	if ($likeService->isLiked($from, $to, $type)) return response(false);
	$userService = UserService::getInstance();
	$user = $userService->getUserByType($feed->poster_id, 'id');

	$data = null;
	$data['owner_id'] = $subject_id;
	$data['type'] = $type;
	$data['owner'] = $user;
	$data['creator'] = $loggedin_user;

	return response($likeService->save($data));
});

$app->delete($container['prefix'].'/likes', function (Request $request, Response $response, array $args) {
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('subject_id', $params)) return response(false);
	if (!array_key_exists('type', $params)) return response(false);

	$from = $loggedin_user->id;
	$subject_id = $params['subject_id'];
	$type = $params['type'];
	if (!in_array($type, ['feed', 'business', 'shop'])) return response(false);

	$like = new Like();
	$like->where = "owner_id = {$loggedin_user->id} AND subject_id = {$subject_id} AND type ='{$type}'";
	return response($like->delete());

});