<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes
// $app->get('/authtoken', function (Request $request, Response $response, array $args) {
$app->post($container['prefix'].'/authtoken', function (Request $request, Response $response, array $args) {
	var_dump($this);
	die();
	$select =  SlimSelect::getInstance();
	// $shops = getShops($this->db, null, $offset = 0, $limit = 1, $load_more = true);
	// var_dump($shops);die();
	
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
	// $user = getUsers($this->db, $conditions, $offset = 0, $limit = 1, $load_more = true);
	$user = $select->getUsers($conditions, 0, 1, true, false);
	if (!$user) return response(false);
	$salt     = $user->salt;
    $password = md5($params['password'] . $salt);
    if ($password == $user->password) {
		unset($user->salt);
		unset($user->password);
    	if($user && $user->activation) {
    		$result = [
    			'status' => false,
    			'validation' => $user
    		];
            return response($result);
        }
        $token = new stdClass;
        $token->token = md5(($user->username).uniqid());
		$token->created = time();
		$token->expried = time()+3600;
		$token->user_guid = $user->guid;
		$token->session_id = session_id();
		$db->saveTableToken($token, $action = "insert", $show_id = false);
		$_SESSION["OSSN_USER"] = $user;
		$_SESSION["TOKEN"] = $token->token;
		return response(["token" => $token->token]);
    }
	return response(false);
})->setName('authtoken');