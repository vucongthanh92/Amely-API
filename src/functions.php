<?php

use Slim\Http\Response;

function responseError($error)
{
	return response([
		'status' => false,
		'error' => $error
	]);
}

function redirectURL($type = 0)
{
	global $settings;

	switch ($type) {
		case 1:
			$type = "/success";
			break;
		default:
			$type = "/error";
			break;
	}
	$url = $settings['responseURL'].$type;

	header("Location: ".$url);
	die();
}

function getFilename()
{
	return time().rand().".jpg";
}

function getInfo($owner_id, $owner_type)
{
	switch ($owner_type) {
		case 'user':
			$userService = UserService::getInstance();
			$user = $userService->getUserByType($owner_id, 'id');
			$user->title = $user->fullname;
			$object = $user;
			$id = $user->id;
			$title = $user->fullname;
			break;
		case 'group':
			$groupService = GroupService::getInstance();
			$group = $groupService->getGroupByType($owner_id, 'id');
			if (!$group) return false;
			if ($group->type == 'user') {
				$userService = UserService::getInstance();
				$user = $userService->getUserByType($group->owner_id, 'id');
				$user->title = $user->fullname;
			}
			$id = $group->id;
			$title = $group->title;
			$object = $group;
			break;
		case 'event':
			$eventService = EventService::getInstance();
			$event = $eventService->getEventByType($owner_id, 'id');
			if (!$event) return false;
			if ($event->type == 'user') {
				$userService = UserService::getInstance();
				$user = $userService->getUserByType($event->owner_id, 'id');
				$user->title = $user->fullname;
			}
			$id = $event->id;
			$title = $event->title;
			$object = $event;
			break;
		default:
			return false;
			break;
	}


	$data = null;
	$data['id'] = $id;
	$data['type'] = $owner_type;
	$data['title'] = $title;
	$data['user'] = $user;
	$data[$owner_type] = $object;

	return $data;
}

function joiner_shuffle($counters)
{
	$result = $counters_id = $counters_item = [];
	foreach ($counters as $key => $counter) {
		array_push($counters_id, $counter->id);
		array_push($counters_item, $counter->item_id);
	}
	shuffle($counters_id);
	foreach ($counters_id as $k => $counter_id) {
		$item_id = $counters_item[$k];
		foreach ($counters as $key => $counter) {
			if ($counter->id == $counter_id) {
				$counter->item_id = $item_id;
				array_push($result, $counter);
			}
		}
	}
	return $result;
}

function convertPrefixOrder($prefix, $order_id, $timestamp)
{
	date_default_timezone_set('Asia/Ho_Chi_Minh');
	$date = date('dmyHis', $timestamp);
	$display_order = $prefix."-".$date."-".$order_id;
	return $display_order;	
}

function null2unknown($data)
{
    if ($data == "") {
        return "No Value Returned";
    } else {
        return $data;
    }
}

function getRealIpAddr()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    {
      $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
      $ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function arrayFilter($objects, $searchedValue, $type = 'id')
{
	$result = array_filter(
	    $objects,
	    function ($e) use (&$searchedValue, $type) {
	        return $e->$type == $searchedValue;
	    }
	);
	if (!$result) return false;
	return array_values($result);
}

function checkPath($path, $type = 'avatar')
{
	if (file_exists(IMAGE_PATH.$path)) return IMAGE_URL.$path;
	if ($type == 'avatar') return AVATAR_DEFAULT;
	return COVER_DEFAULT;
}

function object_cast($destination, $sourceObject)
{
    if (is_string($destination)) {
        $destination = new $destination();
    }

    $sourceReflection = new \ReflectionObject($sourceObject);
    $destinationReflection = new \ReflectionObject($destination);
    $sourceProperties = $sourceReflection->getProperties();
    foreach ($sourceProperties as $sourceProperty) {
        $sourceProperty->setAccessible(true);
        $name = $sourceProperty->getName();
        $value = $sourceProperty->getValue($sourceObject);
        if ($destinationReflection->hasProperty($name)) {
            $propDest = $destinationReflection->getProperty($name);
            $propDest->setAccessible(true);
            $propDest->setValue($destination,$value);
        } else {
            $destination->$name = $value;
        }
    }
    return $destination;
}

function arrayObject($array, $class = 'stdClass') {
    if (empty($array)) {
        return false;
    }
    if ($class=='stdClass')
        return (object) $array;
    
    $object = new $class;
    foreach ($array as $key => $value) {
        if (strlen($key)) {
            if (is_array($value)) {
                $object->{$key} = arrayObject($value, $class);
            } else {
                $object->{$key} = $value;
            }
        }
    }
    return $object;
}

function isUsername($username) 
{
	if(preg_match("/^[a-zA-Z0-9]+$/", $username) && strlen($username) > 4) {
			return true;
	}
	return false;
}

function isNumberPhone($mobilelogin) {
	if (preg_match("/^\\+?\d+$/i", $mobilelogin)) {
		return true;
	}
	return false;
}

function loggedin_user()
{
	return forceObject($_SESSION['OSSN_USER']);
}

function insertFirebase($path, $params)
{
	global $Ossn;
	$firebase = new \Geckob\Firebase\Firebase($Ossn->firebase_key);
	$firebase = $firebase->setPath($path);
	if ($params) {
		foreach ($params as $key => $value) {
			$firebase->set($key, (string)$value);
		}
	}
	return true;
}

function get2Relationships($type, $owner_guid)
{
	$select = SlimSelect::getInstance();

	$relation_params = null;
	$relation_params[] = [
		'key' => 'amely_relationships r1',
		'value' => "amely_relationships r1 on r.relation_from = r1.relation_to",
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
}

function getInvitation($invite_type, $approve_type, $owner_guid, $reverse = false)
{
	$db = SlimDatabase::getInstance();
	$query = "select * from amely_relationships r where r.type = '{$invite_type}' AND r.relation_to = {$owner_guid} AND r.relation_from NOT IN (select r1.relation_to from amely_relationships r1 where r1.type = '{$approve_type}' AND r1.relation_from = {$owner_guid})";
	if ($reverse) {
		$query = "select * from amely_relationships r where r.type = '{$invite_type}' AND r.relation_from = {$owner_guid} AND r.relation_to NOT IN (select r1.relation_from from amely_relationships r1 where r1.type = '{$approve_type}' AND r1.relation_to = {$owner_guid})";
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


function response($result)
{
	$response = new Response();
	if ($result === false) {
		return $response->withJson([
			'status' => false,
			'error' => ERROR_1
		]);
	}
	if ($result === true) {
		return $response->withJson([
			'status' => true
		]);
	}
	if (is_numeric($result)) {
		return $response->withJson([
			'status' => true,
			'id' => $result
		]);
	}
    
	return $response->withJson($result, 200, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}












