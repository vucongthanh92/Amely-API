<?php

use Slim\Http\Response;

function loggedin_user()
{
	return $_SESSION["OSSN_USER"];
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

function getPrice(Object $product)
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
	$db =  SlimDatabase::getInstance();
	$table = "ossn_usertokens";
	$conditions = null;
	$conditions[] = [
		'key' => 'token',
		'value' => "= '{$token}'",
		'operation' => ""
	];
	$token = $db->getData($table, $conditions, $offset = 0, $limit = 1, $load_more = true);
	if ($token) return true;
	return false;
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