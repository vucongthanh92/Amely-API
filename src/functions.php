<?php

use Slim\Http\Response;

function loggedin_user()
{
	return forceObject($_SESSION['OSSN_USER']);
}

function getFriendsGUID($owner_guid)
{
	$select = SlimSelect::getInstance();

	$relation_params = null;
    $relation_params[] = [
    	'key' => 'type',
    	'value' => "= 'friend:request'",
    	'operation' => ''
    ];
    $relation_params[] = [
    	'key' => 'relation_to',
    	'value' => "= {$owner_guid}",
    	'operation' => 'AND'
    ];
    $relation_params[] = [
    	'key' => 'relation_from',
    	'value' => '',
    	'operation' => 'query_params'
    ];
    
    $relations = $select->getRelationships($relation_params,0,99999999);
    if ($relations) {
    	$relations_from = array_map(create_function('$o', 'return $o->relation_from;'), $relations);
    	$relations_from = implode(",", $relations_from);

	    $relation_params = null;
	    $relation_params[] = [
	    	'key' => 'type',
	    	'value' => "= 'friend:request'",
	    	'operation' => ''
	    ];
	    $relation_params[] = [
	    	'key' => 'relation_from',
	    	'value' => "= {$owner_guid}",
	    	'operation' => 'AND'
	    ];
	    $relation_params[] = [
	    	'key' => 'relation_to',
	    	'value' => "IN ($relations_from)",
	    	'operation' => 'AND'
	    ];
	    
	    $friends = $select->getRelationships($relation_params,0,99999999);
	    $friends_guid = array_map(create_function('$o', 'return $o->relation_to;'), $friends);
	    return $friends_guid;

	}
	return false;
}


function forceObject(&$object) {
    if (!is_object($object) && gettype($object) == 'object'
    )
        return ($object = unserialize(serialize($object)));
    return $object;
}

function compareAds($advertises)
{
	foreach ($advertises as &$advertise) {
        if (count($advertise) > 1) {
            usort($advertise, function($ads1, $ads2)
            {
                return $ads1->balance > $ads2->balance ? -1 : 1;
            });
        }
    }
    return $advertises;
}

function getPrice($product)
{
	if (!empty($product->sale_price)) return $product->sale_price;
	return $product->price;
}

function conditionAds()
{
	date_default_timezone_set('Asia/Ho_Chi_Minh');
	$current_time = time();
	$time = date("H:i:s", $current_time);
	$conditions[] = [
		'key' => 'start_date',
		'value' => "< {$current_time}",
		'operation' => ''
	];
	$conditions[] = [
		'key' => 'end_date',
		'value' => "> {$current_time}",
		'operation' => 'AND'
	];
	$conditions[] = [
		'key' => "concat('{$time}')",
		'value' => "> concat(start_time,':00')",
		'operation' => 'AND'
	];
	$conditions[] = [
		'key' => "concat('{$time}')",
		'value' => "< concat(end_time,':00')",
		'operation' => 'AND'
	];
	$conditions[] = [
		'key' => '(budget*1 - cpc*1)',
		'value' => ">= amount*1",
		'operation' => 'AND'
	];
	$conditions[] = [
		'key' => "approved",
		'value' => "NOT IN ('new', 'suspended')",
		'operation' => 'AND'
	];
	$conditions[] = [
		'key' => "enabled",
		'value' => "= 1",
		'operation' => 'AND'
	];
    return $conditions;
}

function checkToken($token)
{
	$select = SlimSelect::getInstance();
	
	$conditions = null;
	$conditions[] = [
		'key' => 'token',
		'value' => "= '{$token}'",
		'operation' => ""
	];
	$token = $select->getTokens($conditions, $offset = 0, $limit = 1, $load_more = true);
	if ($token) {
		$user_params = null;
		$user_params[] = [
			'key' => 'guid',
			'value' => "= {$token->user_guid}",
			'operation' => ''
		];
		$user = $select->getUsers($user_params, $offset = 0, $limit = 1, $load_more = true, $getAddr = true);
	    session_id($token->session_id);
	    session_reset();
	    $_SESSION["OSSN_USER"] = $user;
	}
	return response(false);
}

function response($result)
{
	$response = new Response();
	if ($result === false) {
		return $response->withJson([
			'status' => false
		]);
	}
	if ($result === true) {
		return $response->withJson([
			'status' => true
		]);
	}
	if (is_numeric($result)) {
		return $response->withJson([
			'guid' => $result
		]);
	}
    
	return $response->withJson($result, 200, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}