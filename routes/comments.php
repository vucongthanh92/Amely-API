<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/comments', function (Request $request, Response $response, array $args) {
	$commentService = CommentService::getInstance();
	$userService = UserService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('subject_id', $params)) return response(false);
	if (!array_key_exists('type', $params)) return response(false);
	if (!array_key_exists('limit', $params)) $params['limit'] = 10;
	if (!array_key_exists('offset', $params)) $params['offset'] = 0;
	$subject_id = $params['subject_id'];
	$type = $params['type'];
	$limit = $params['limit'];
	$offset = $params['offset'];

	if (!in_array($type, ['feed', 'product'])) return response(false);

	$comment_params = null;
	$comment_params[] = [
		'key' => 'type',
		'value' => "= '{$type}'",
		'operation' => ''
	];
	$comment_params[] = [
		'key' => 'subject_id',
		'value' => "= {$subject_id}",
		'operation' => 'AND'
	];
	$comments = $commentService->getComments($comment_params, $offset, $limit);
	if (!$comments) return response(false);
	$owners_id = [];
	foreach ($comments as $key => $comment) {
		array_push($owners_id, $comment->owner_id);
	}
	if (!$owners_id) return response(false);
	$owners_id = array_unique($owners_id);
	$owners_id = implode(',', $owners_id);
	$users = $userService->getUsersByType($owners_id, 'id', 0, 9999999, false);
	foreach ($comments as $key => $comment) {
		$owner = arrayFilter($users, $comment->owner_id);
		$comment->owners = $owner;
		$comments[$key] = $comment;
	}
	return response($comments);

});

$app->put($container['prefix'].'/comments', function (Request $request, Response $response, array $args) {
	$commentService = CommentService::getInstance();
	$services = Services::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('subject_id', $params)) return response(false);
	if (!array_key_exists('type', $params)) return response(false);
	if (!array_key_exists('content', $params)) $params['content'] = false;
	if (!array_key_exists('images', $params)) $params['images'] = false;
	if (!in_array($params['type'], ['feed', 'business'])) return response(false);

	switch ($params['type']) {
		case 'feed':
			$feedService = FeedService::getInstance();
			$feed = $feedService->getFeedByType($params['subject_id'], 'id');
			if (!$feed) return response(false);
			$userService = UserService::getInstance();
			$user = $userService->getUserByType($feed->poster_id, 'id');
			break;
		
		default:
			# code...
			break;
	}
	$data = null;
	$data['owner_id'] = $params['subject_id'];
	$data['type'] = $params['type'];
	$data['creator'] = $loggedin_user;
	$data['owner'] = $user;
	$data['content'] = $params['content'];
	$data['images'] = $params['images'];

	if ($commentService->save($data)) {
		$notificationService = NotificationService::getInstance();
		$notify_params = null;
		switch ($data['type']) {
			case 'feed':
				$notification_type = "comment:feed";
				$notify_params['from'] = $loggedin_user;
				$notify_params['to'] = $user;
				$notify_params['subject_id']  = $data['owner_id'];
				break;
			case 'shop':
				$shopService = ShopService::getInstance();
				$shop = $shopService->getShopByType($data['owner_id'], 'id');
				$owner = getInfo($shop->owner_id, 'user');
				$notify_params['from'] = $loggedin_user;
				$notify_params['to'] = $owner;
				$notify_params['subject_id']  = $data['owner_id'];
				$notification_type = "like:shop";
				break;
			case 'product':
				$notify_params['product_id']  = $data['owner_id'];
				$notification_type = "like:product";
				break;
			default:
				return false;
				break;
		}
		$notificationService->save($notify_params, $notification_type);
		return response(true);
	}
	return response(false);
});

$app->delete($container['prefix'].'/comments', function (Request $request, Response $response, array $args) {
	$commentService = CommentService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('comment_id', $params)) return response(false);
	$comment = $commentService->getCommentById($params['comment_id']);
	if (!$comment) return response(false);
	$comment = object_cast("Annotation", $comment);	
	if ($loggedin_user->id != $comment->owner_id) return response(false);
	$comment->where = "id = {$comment->id}";
	return response($comment->delete());

});