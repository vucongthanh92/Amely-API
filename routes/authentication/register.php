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

	if (!isUsername($params["username"])) return response(false);
	if (!isNumberPhone($params["mobilelogin"])) return response(false);
	if (!$params["password"]) return response(false);
	$username = $params["username"];
	$mobilelogin = $params["mobilelogin"];
	$email = $params["email"];
	$password = $params["password"];

	$user_params = null;
	$user_params[] = [
		'key' => 'username',
		'value' => "= '{$username}'",
		'operation' => ''
	];
	$user = $select->getUsers($user_params, 0, 1, false);
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
		$user = $select->getUsers($user_params, 0, 1, false);
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
		$user = $select->getUsers($user_params, 0, 1, false);
		if ($user) return response([
	    	"status" => false,
	    	"error" => "email_exist"
	    ]);
	}
	
	$salt = substr(uniqid(), 5);
	$activation = md5($password . time() . rand());
	$password = md5($password . $salt);
	$code = rand(100000, 999999);

	$insert = new stdClass;
	$insert->type = "normal";
	$insert->username = $username;
	$insert->email = $email;
	$insert->password = $password;
	$insert->salt = $salt;
	$insert->first_name = $params['firstname'];
	$insert->last_name = $params['lastname'];
	$insert->last_login = 0;
	$insert->last_activity = 0;
	$insert->activation = $activation;
	$insert->mobilelogin = preg_replace("/^\\+?84/i", "0", $mobilelogin);
	$insert->verification_code = $code;
	$insert->time_created = time();
	$insert->birthdate = "1993-08-03";
	$insert->gender = "male";

	$object = new stdClass;
	$object->insert = $insert;
	$insert_guid = $db->saveTable($object, "amely_users", "insert", true);
	if ($insert_guid) {
		return response(Services::getInstance()->sendByMobile($insert->mobilelogin, $code));
	}
	return response(false);
})->setName('register');