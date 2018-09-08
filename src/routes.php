<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/users', function (Request $request, Response $response, array $args) {
	$table = "ossn_users";
	$conditions = null;
	$conditions[] = [
		'key' => 'guid',
		'value' => 'DESC',
		'operation' => 'order_by'
	];
	$users = getData($this->db, $table, $conditions, $offset = 0, $limit = 10, $load_more = true);
	if (!$users) return $response->withStatus(false, "error");
    
	return $response->withJson($users, 200, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
});