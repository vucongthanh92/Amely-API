<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/register', function (Request $request, Response $response, array $args) {

	$db = SlimDatabase::getInstance();
	$select = SlimSelect::getInstance();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("username", $params))  	$params["username"] = false;
	if (!array_key_exists("firstname", $params)) 	$params["firstname"] = false;
	if (!array_key_exists("lastname", $params))  	$params["lastname"] = false;
	if (!array_key_exists("email", $params)) 	 	$params["email"] = false;
	if (!array_key_exists("password", $params))  	$params["password"] = false;
	if (!array_key_exists("birthdate", $params)) 	$params["birthdate"] = false;
	if (!array_key_exists("gender", $params))  	 	$params["gender"] = false;
	if (!array_key_exists("mobilelogin", $params)) 	$params["mobilelogin"] = false;

	if (!isUsername($params['username'])) return response(false);
	if (!isNumberPhone($params['mobilelogin'])) return response(false);

	$username = $params['username'];
	$mobilelogin = $params['mobilelogin'];
	$email = $params['email'];

	$user_params = null;
	$user_params[] = [
		'key' => 'username',
		'value' => "= '{$username}'",
		'operation' => ''
	];
	$user = $select->getUsers($user_param, 0, 1, false);
	if ($user) return response([
    	"status" => false,
    	"error" => "username_exist"
    ]);

	if ($mobilelogin) {
		$user_params[] = [
			'key' => 'mobilelogin',
			'value' => "= '{$mobilelogin}'",
			'operation' => 'OR'
		];
		$user = $select->getUsers($user_param, 0, 1, false);
		if ($user) return response([
	    	"status" => false,
	    	"error" => "mobile_exist"
	    ]);
	}

	if ($email) {
		$user_params[] = [
			'key' => 'email',
			'value' => "= '{$email}'",
			'operation' => 'OR'
		];
		$user = $select->getUsers($user_param, 0, 1, false);
		if ($user) return response([
	    	"status" => false,
	    	"error" => "email_exist"
	    ]);
	}
	
	$salt = substr(uniqid(), 5);
	$activation = md5($password . time() . rand());
	$password = md5($password . $salt);


	$object = new stdClass;
	$object->type = "normal";
	$object->username = $username;
	$object->email = $email;
	$object->password = $password;
	$object->salt = $salt;
	$object->first_name = $params['first_name'];
	$object->last_name = $params['last_name'];
	$object->last_login = 0;
	$object->last_activity = 0;
	$object->activation = $activation;
	$object->mobilelogin = preg_replace("/^\\+?84/i", "0", $mobilelogin);

	if ($db->saveTableUsers($object, "insert")){
		$services = Services::getInstance();
		$code = rand(100000, 999999);

		if ($services->sendByMobile($object->mobilelogin, $code)) {
			$update = new stdClass;
			$update->verification_code = $code;

			$object_tmp = new stdClass;
			$object_tmp->update = $update;
			$object_tmp->where = "guid= '{$guid}'";
			return $db->updateTable($object_tmp, 'ossn_users');
		}
		return true;
	}
	return response(false);
})->setName('register');