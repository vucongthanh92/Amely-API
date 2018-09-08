<?php

use Slim\Http\Response;

function checkToken($db, $token)
{
	$table = "ossn_usertokens";
	$conditions = null;
	$conditions[] = [
		'key' => 'token',
		'value' => "= '{$token}'",
		'operation' => ""
	];
	$token = getData($db, $table, $conditions, $offset = 0, $limit = 1, $load_more = true);
	if ($token) return $token->user_guid;
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