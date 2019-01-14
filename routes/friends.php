<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/friends', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$relationshipService = RelationshipService::getInstance();

    $loggedin_user = loggedin_user();
    $params = $request->getQueryParams();

    if (array_key_exists("user_id", $params) && is_numeric($params['user_id'])) {
        $user = $userService->getUserByType($params['user_id'], 'id', false);
    } else {
        $user = $loggedin_user;
    }
    if (!$user) return response(false);

    $friends_id = $relationshipService->getFriendsGUID($user->id);
    $list_request_from = $relationshipService->getRelationsByType($user->id, false, 'friend:request', 0, 99999999);
    $list_request_to = $relationshipService->getRelationsByType(false, $user->id, 'friend:request', 0, 99999999);
    if ($list_request_from) {
        $list_request_from = array_map(create_function('$o', 'return $o->relation_to;'), $list_request_from);
    } else {
        $list_request_from = [];
    }
    if ($list_request_to) {
        $list_request_to = array_map(create_function('$o', 'return $o->relation_from;'), $list_request_to);
    } else {
        $list_request_to = [];
    }
    $friends_id = array_intersect(array_unique($list_request_from), array_unique($list_request_to));

    if (!$friends_id) return response(false);
    $friends_id = implode(",", array_unique($friends_id));
	if ($user->id != $loggedin_user->id) {
		$friends_requested_id = $relationshipService->getFriendRequested($loggedin_user->id, $friends_id);
	}

	$friends = $userService->getUsersByType($friends_id, 'id', 0, 99999999, false);
	foreach ($friends as $key => $friend) {
		if ($friend->id != $loggedin_user->id) {
            if ($user->id != $loggedin_user->id) {
                if ($friends_requested_id) {
    			    if (in_array($friend->id, $friends_requested_id)) {
    	            	$friend->requested = 1;
    			    }
                }
            }
		}
	}
	$friends = array_values($friends);
    if (!$friends) return response(false);
	return response($friends);
});

$app->delete($container['prefix'].'/friends', function (Request $request, Response $response, array $args) {
    $relationshipService = RelationshipService::getInstance();
    $loggedin_user = loggedin_user();
    $params = $request->getQueryParams();
    if (!array_key_exists('user_id', $params)) $params['user_id'] = false;

    if (!$params['user_id']) return response(false);
    if (!is_numeric($params['user_id'])) return response(false);
    if ($loggedin_user->id == $params['user_id']) return response(false);

    return response($relationshipService->deleteFriend($loggedin_user->id, $params['user_id']));
});