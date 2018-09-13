<?php

use Slim\Http\Response;

function loggedin_user()
{
	return forceObject($_SESSION['OSSN_USER']);
}

function get2Relationships($type, $owner_guid)
{
	$select = SlimSelect::getInstance();

	$relation_params = null;
	$relation_params[] = [
		'key' => 'ossn_relationships r1',
		'value' => "ossn_relationships r1 on r.relation_from = r1.relation_to",
		'operation' => 'JOIN'
	];
	$relation_params[] = [
		'key' => 'r.relation_to',
		'value' => "= r1.relation_from",
		'operation' => ''
	];
    $relation_params[] = [
    	'key' => 'r.type',
    	'value' => "= 'friend:request'",
    	'operation' => 'AND'
    ];
    $relation_params[] = [
    	'key' => 'r.relation_from',
    	'value' => "= {$owner_guid}",
    	'operation' => 'AND'
    ];
    $relation_params[] = [
    	'key' => 'r.relation_from',
    	'value' => '',
    	'operation' => 'query_params'
    ];
    $relation_params[] = [
    	'key' => 'r.relation_to',
    	'value' => '',
    	'operation' => 'query_params'
    ];

    $friends = $select->getRelationships($relation_params,0,99999999);
    if (!$friends) return false;
    $friends_guid = array_map(create_function('$o', 'return $o->relation_to;'), $friends);
    return $friends_guid;

	// select * from ossn_relationships r JOIN ossn_relationships r1 on r.relation_from = r1.relation_to where r.relation_to = r1.relation_from AND r.type = "friend:request" AND r.relation_from = 27
}

function getInvitation($invite_type, $approve_type, $owner_guid, $reverse = false)
{
	$db = SlimDatabase::getInstance();
	$query = "select * from ossn_relationships r where r.type = '{$invite_type}' AND r.relation_to = {$owner_guid} AND r.relation_from NOT IN (select r1.relation_to from ossn_relationships r1 where r1.type = '{$approve_type}' AND r1.relation_from = {$owner_guid})";
	if ($reverse) {
		$query = "select * from ossn_relationships r where r.type = '{$invite_type}' AND r.relation_from = {$owner_guid} AND r.relation_to NOT IN (select r1.relation_from from ossn_relationships r1 where r1.type = '{$approve_type}' AND r1.relation_to = {$owner_guid})";
	}
	$invitations = $db->query($query);
	if (!$invitations) return false;
	if ($reverse) {
		$invitations_guid = array_map(create_function('$o', 'return $o->relation_to;'), $invitations);
	} else {
    	$invitations_guid = array_map(create_function('$o', 'return $o->relation_from;'), $invitations);
	}
    return $invitations_guid;
}

function getMembersGUID($type, $owner_guid, $count = false)
{
	$select = SlimSelect::getInstance();
	$limit = 99999999;
	$relation_params = null;
	switch ($type) {
		case 'group':
			$relation_params[] = [
				'key' => 'type',
				'value' => "IN ('group:join:approve', 'group:invite:approve')",
				'operation' => ''
			];
			break;
		case 'event':
			$relation_params[] = [
				'key' => 'type',
				'value' => "IN ('event:join:approve', 'event:invite:approve')",
				'operation' => ''
			];
			break;
		default:
			return  false;
			break;
	}
	
	$relation_params[] = [
		'key' => 'relation_from',
		'value' => "IN ({$owner_guid})",
		'operation' => 'AND'
	];
	if ($count) {
		$relation_params[] = [
			'key' => '*',
			'value' => "count",
			'operation' => 'count'
		];
		$limit = 1;
		$relation_params[] = [
			'key' => 'relation_from',
			'value' => "",
			'operation' => 'group_by'
		];
	}
	$relation_params[] = [
		'key' => 'relation_from',
		'value' => "",
		'operation' => 'query_params'
	];
	$relation_params[] = [
		'key' => 'relation_to',
		'value' => "",
		'operation' => 'query_params'
	];
	

	$relations = $select->getRelationships($relation_params, 0, $limit);
	if (!$relations) return false;
	if ($count) {
		return $relations;
	}

    // $relations_guid = array_map(create_function('$o', 'return $o->relation_to;'), $relations);
    return $relations;
}

function getPagesGUID($owner_guid)
{
	$select = SlimSelect::getInstance();
	$relation_params = null;
	$relation_params[] = [
		'key' => 'type',
		'value' => "IN ('business:join:approve')",
		'operation' => ''
	];
	$relation_params[] = [
		'key' => 'relation_to',
		'value' => "= {$owner_guid}",
		'operation' => 'AND'
	];
	$relations = $select->getRelationships($relation_params,0,99999999);
	if (!$relations) return false;
    $pages_guid = array_map(create_function('$o', 'return $o->relation_from;'), $relations);
    return $pages_guid;
}

function getGroupsGUID($owner_guid)
{
	$select = SlimSelect::getInstance();
	$relation_params = null;
	$relation_params[] = [
		'key' => 'type',
		'value' => "IN ('group:join:approve', 'group:invite:approve')",
		'operation' => ''
	];
	$relation_params[] = [
		'key' => 'relation_to',
		'value' => "= {$owner_guid}",
		'operation' => 'AND'
	];
	$relations = $select->getRelationships($relation_params,0,99999999);
	if (!$relations) return false;
    $groups_guid = array_map(create_function('$o', 'return $o->relation_from;'), $relations);
    return $groups_guid;
}

function getFriendsGUID($owner_guid)
{
	$select = SlimSelect::getInstance();

	$relation_params = null;
	$relation_params[] = [
		'key' => 'ossn_relationships r1',
		'value' => "r.relation_from = r1.relation_to",
		'operation' => 'JOIN'
	];
	$relation_params[] = [
		'key' => 'r.relation_to',
		'value' => "= r1.relation_from",
		'operation' => ''
	];
    $relation_params[] = [
    	'key' => 'r.type',
    	'value' => "= 'friend:request'",
    	'operation' => 'AND'
    ];
    $relation_params[] = [
    	'key' => 'r.relation_from',
    	'value' => "= {$owner_guid}",
    	'operation' => 'AND'
    ];
    $relation_params[] = [
    	'key' => 'r.relation_to',
    	'value' => '',
    	'operation' => 'query_params'
    ];

    $friends = $select->getRelationships($relation_params,0,99999999);
    if (!$friends) return false;
    $friends_guid = array_map(create_function('$o', 'return $o->relation_to;'), $friends);
    return $friends_guid;
	// $select = SlimSelect::getInstance();

	// $relation_params = null;
 //    $relation_params[] = [
 //    	'key' => 'type',
 //    	'value' => "= 'friend:request'",
 //    	'operation' => ''
 //    ];
 //    $relation_params[] = [
 //    	'key' => 'relation_to',
 //    	'value' => "= {$owner_guid}",
 //    	'operation' => 'AND'
 //    ];
 //    $relation_params[] = [
 //    	'key' => 'relation_from',
 //    	'value' => '',
 //    	'operation' => 'query_params'
 //    ];
    
 //    $relations = $select->getRelationships($relation_params,0,99999999);
 //    if ($relations) {
 //    	$relations_from = array_map(create_function('$o', 'return $o->relation_from;'), $relations);
 //    	$relations_from = implode(",", $relations_from);

	//     $relation_params = null;
	//     $relation_params[] = [
	//     	'key' => 'type',
	//     	'value' => "= 'friend:request'",
	//     	'operation' => ''
	//     ];
	//     $relation_params[] = [
	//     	'key' => 'relation_from',
	//     	'value' => "= {$owner_guid}",
	//     	'operation' => 'AND'
	//     ];
	//     $relation_params[] = [
	//     	'key' => 'relation_to',
	//     	'value' => "IN ($relations_from)",
	//     	'operation' => 'AND'
	//     ];
	    
	//     $friends = $select->getRelationships($relation_params,0,99999999);
	//     $friends_guid = array_map(create_function('$o', 'return $o->relation_to;'), $friends);
	//     return $friends_guid;

	// }
	// return false;
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
		session_id($token->session_id);
		session_reset();
		if ($token->token != $_SESSION["TOKEN"]) {
			$user_params = null;
			$user_params[] = [
				'key' => 'guid',
				'value' => "= {$token->user_guid}",
				'operation' => ''
			];
			$user = $select->getUsers($user_params, $offset = 0, $limit = 1, $load_more = true, $getAddr = true);
		    
		    $_SESSION["OSSN_USER"] = $user;
		}
		return true;
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