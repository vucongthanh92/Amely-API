<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/comments', function (Request $request, Response $response, array $args) {
	$commentService = CommentService::getInstance();
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
	return response($comments);

});

$app->put($container['prefix'].'/comments', function (Request $request, Response $response, array $args) {
	$commentService = CommentService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('subject_id', $params)) return response(false);
	if (!array_key_exists('type', $params)) return response(false);
	if (!array_key_exists('content', $params)) $params['content'] = false;
	if (!array_key_exists('images', $params)) $params['images'] = false;

	if (!in_array($params['type'], ['feed', 'business'])) return response(false);

	$comment = new Annotation();
	$comment->data->owner_id = $loggedin_user->id;
	$comment->data->subject_id = $params['subject_id'];
	$comment->data->type = $params['type'];
	$comment->data->content = "";
	$comment->data->images = "";

	return response($comment->insert());
});

$app->delete($container['prefix'].'/comments', function (Request $request, Response $response, array $args) {
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('subject_id', $params)) return response(false);
	if (!array_key_exists('type', $params)) return response(false);

	$from = $loggedin_user->id;
	$to = $subject_id;
	$type = $params['type'];
	$subject_id = $params['subject_id'];
	if (!in_array($type, ['feed', 'business'])) return response(false);

	$like = new Like();
	$like->where = "owner_id = {$loggedin_user->id} AND subject_id = {$subject_id} AND type ='{$type}'";
	return response($like->delete());

});