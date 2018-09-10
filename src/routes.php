<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes
require __DIR__ . '/../routes/authentication/login.php';
require __DIR__ . '/../routes/profile.php';
require __DIR__ . '/../routes/services.php';
require __DIR__ . '/../routes/friends.php';
require __DIR__ . '/../routes/product_group.php';
require __DIR__ . '/../routes/feeds.php';



// $app->get('/users', function (Request $request, Response $response, array $args) {
// 	// $table = "ossn_users";
// 	// $conditions = null;
// 	// $conditions[] = [
// 	// 	'key' => 'guid',
// 	// 	'value' => 'DESC',
// 	// 	'operation' => 'order_by'
// 	// ];
// 	// $users = getData($this->db, $table, $conditions, $offset = 0, $limit = 10, $load_more = true);

// 	$en = new stdClass();
// 	$en->abc = "1111111";
// 	$en->def = "222222";
	

// 	$test = new stdClass();
// 	$test->guid = 12589;
// 	$test->owner_guid = 27;
// 	$test->type = "object";
// 	$test->subtype = "test";
// 	$test->data = $en;
// 	return response(updateEAV($this->db, $test));
// die('2131');
// 	return response(insertEAV($this->db, $test));
// 	// return response($users, $response);
// });