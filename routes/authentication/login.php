<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes
// $app->get('/authtoken', function (Request $request, Response $response, array $args) {
$app->post($container['prefix'].'/authtoken', function (Request $request, Response $response, array $args) {
	$db = SlimDatabase::getInstance();
	$select =  SlimSelect::getInstance();
	
	$params = $request->getParsedBody();
	$table = "users";
	$conditions = null;
	if (!array_key_exists("username", $params)) return response(false);
	if (!array_key_exists("password", $params)) return response(false);

	if(strpos($params['username'], '@') !== false) {
		$conditions[] = [
			'key' => 'email',
			'value' => "= '{$params['username']}'",
			'operation' => ''
		];
	}
	if (is_numeric($params['username'])) {
		$conditions[] = [
			'key' => 'mobilelogin',
			'value' => "= '{$params['username']}'",
			'operation' => ''
		];
	} else {
		$conditions[] = [
			'key' => 'username',
			'value' => "= '{$params['username']}'",
			'operation' => ''
		];
	}
	$test = new User();
	var_dump($test->getUser($conditions));
	die();
	$user = (new User)->getUser($conditions);
	var_dump($user);
	die('12345');
	// $user = getUsers($this->db, $conditions, $offset = 0, $limit = 1, $load_more = true);
	$user = $select->getUsers($conditions, 0, 1, true, false);
	if (!$user) return response(false);
	$salt     = $user->salt;
    $password = md5($params['password'] . $salt);
    if ($password == $user->password) {
		unset($user->password);
		unset($user->salt);
		unset($user->verification_code);
		
    	if($user && $user->activation) {
			unset($user->activation);
    		$result = [
    			'status' => false,
    			'validation' => $user
    		];
            return response($result);
        }
        $insert = new stdClass;
        $insert->token = md5(($user->username).uniqid());
		$insert->created = time();
		$insert->expired = time()+3600;
		$insert->user_guid = $user->id;
		$insert->session_id = session_id();

		$object = new stdClass;
		$object->insert = $insert;
        if ($db->saveTable($object, "amely_usertokens", "insert", false)) {
			$_SESSION["OSSN_USER"] = $user;
			$_SESSION["TOKEN"] = $insert->token;
			return response(["token" => $insert->token]);
        }
    }
	return response(false);
})->setName('authtoken');