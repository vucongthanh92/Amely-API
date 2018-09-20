<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/friends', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
    $loggedin_user = loggedin_user();
    $params = $request->getQueryParams();

    if (array_key_exists("user_guid", $params) && is_numeric($params['user_guid'])) {
    	$user_params = null;
    	$user_params[] = [
    		'key' => 'guid',
    		'value' => "= {$params['user_guid']}",
    		'operation' => ''
    	];
        $user = $select->getUsers($user_params,0,1,false);
    } else {
        $user = $loggedin_user;
    }
    if (!$user) return response(false);

    $friends_guid = getFriendsGUID($loggedin_user->guid);
    $friends_guid = implode(",", array_unique($friends_guid));
	if ($user->guid != $loggedin_user->guid) {
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
	    	'value' => "IN ($friends_guid)",
	    	'operation' => 'AND'
	    ];
	    $relation_params[] = [
	    	'key' => 'relation_to',
	    	'value' => '',
	    	'operation' => 'query_params'
	    ];
	    $friends_requested = $select->getRelationships($relation_params,0,99999999);
        if ($friends_requested) {
	      $friends_requested_guid = array_map(create_function('$o', 'return $o->relation_to;'), $friends_requested);
        }
	}

	$user_params = null;
	$user_params[] = [
		'key' => 'guid',
		'value' => "IN ({$friends_guid})",
		'operation' => ''
	];
	$friends = $select->getUsers($user_params,0,99999999,false);
	foreach ($friends as $key => $friend) {
		if ($friend->guid != $loggedin_user->guid) {
            if ($user->guid != $loggedin_user->guid) {
                if ($friends_requested_guid) {
    			    if (in_array($friend->guid, $friends_requested_guid)) {
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
    $delete = SlimDelete::getInstance();
    $loggedin_user = loggedin_user();
    $params = $request->getQueryParams();
    if (!array_key_exists('user_guid', $params)) $params['user_guid'] = false;

    if (!$params['user_guid']) return response(false);
    if (!is_numeric($params['user_guid'])) return response(false);

    return response($delete->friend($loggedin_user->guid, $params['user_guid']));
});