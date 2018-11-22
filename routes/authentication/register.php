<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->put($container['prefix'].'/register', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();

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

	$user = $userService->getUserByType($username, 'username', false, false);
	if ($user) return response([
    	"status" => false,
    	"error" => "username_exist"
    ]);

	if ($mobilelogin) {
		$user = $userService->getUserByType($mobilelogin, 'mobilelogin', false, false);
		if ($user) return response([
	    	"status" => false,
	    	"error" => "mobile_exist"
	    ]);
	}

	if ($email) {
		$user = $userService->getUserByType($email, 'email', false, false);
		if ($user) return response([
	    	"status" => false,
	    	"error" => "email_exist"
	    ]);
	}

	$salt = substr(uniqid(), 5);
	$activation = md5($password . time() . rand());
	$password = md5($password . $salt);
	$code = rand(100000, 999999);

	$user_data = null;
	$user_data['type'] = "normal";
	$user_data['username'] = $username;
	$user_data['email'] = $email;
	$user_data['password'] = $password;
	$user_data['salt'] = $salt;
	$user_data['first_name'] = $params['firstname'];
	$user_data['last_name'] = $params['lastname'];
	$user_data['last_login'] = 0;
	$user_data['last_activity'] = 0;
	$user_data['activation'] = $activation;
	$mobile = preg_replace("/^\\+?84/i", "0", $mobilelogin);
	$user_data['mobilelogin'] = $mobile;
	$user_data['verification_code'] = $code;
	$user_data['time_created'] = time();
	$user_data['birthdate'] = "1993-08-03";
	$user_data['gender'] = "male";
	$user_data['usercurrency'] = "VND";
	$user_id = $userService->save($user_data);

	// $user = new User;
	// $user->data->type = "normal";
	// $user->data->username = $username;
	// $user->data->email = $email;
	// $user->data->password = $password;
	// $user->data->salt = $salt;
	// $user->data->first_name = $params['firstname'];
	// $user->data->last_name = $params['lastname'];
	// $user->data->last_login = 0;
	// $user->data->last_activity = 0;
	// $user->data->activation = $activation;
	// $user->data->mobilelogin = preg_replace("/^\\+?84/i", "0", $mobilelogin);
	// $user->data->verification_code = $code;
	// $user->data->time_created = time();
	// $user->data->birthdate = "1993-08-03";
	// $user->data->gender = "male";
	// $user->data->usercurrency = "VND";

	$user_id = $user->insert(true);
	if ($user_id) {
		return response(Services::getInstance()->sendByMobile($mobile, $code));
	}
	return response(false);
})->setName('register');