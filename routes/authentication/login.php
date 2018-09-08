<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes
// $app->get('/authtoken', function (Request $request, Response $response, array $args) {
$app->post('/authtoken', function (Request $request, Response $response) {
	
	$input = $request->getParsedBody();
	$table = "users";
	$conditions = null;
	if(strpos($input['username'], '@') !== false) {
		$conditions[] = [
			'key' => 'email',
			'value' => "= '{$input['username']}'",
			'operation' => ''
		];
	}
	if (is_numeric($input['username'])) {
		$conditions[] = [
			'key' => 'mobilelogin',
			'value' => "= '{$input['username']}'",
			'operation' => ''
		];
	} else {
		$conditions[] = [
			'key' => 'username',
			'value' => "= '{$input['username']}'",
			'operation' => ''
		];
	}
	// $user = getUsers($this->db, $conditions, $offset = 0, $limit = 1, $load_more = true);
	// var_dump($user);die();
	$users = getData($this->db, $table, $conditions, $offset = 0, $limit = 1, $load_more = true);
	$user = $users[0];
	$salt     = $user->salt;
    $password = md5($input['password'] . $salt);
    if ($password == $user->password) {
    	if($user && $user->activation) {
    		unset($user->salt);
    		unset($user->password);
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
		saveTableToken($this->db, $token, $action = "insert", $show_id = false);
		return response(["token" => $token->token]);
    }
	return response(false);
});