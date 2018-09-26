<?php

use Slim\Http\Response;

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