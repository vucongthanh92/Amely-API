<?php
use Slim\Http\Request;
use Slim\Http\Response;

// thong tin chi tiet 1 user
$app->get($container['administrator'].'/users', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$shopService = ShopService::getInstance();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('username', $params)) return response(false);

	$user = $userService->getUserByType($params['username'], 'username', true);

	$shop = $shopService->getShopByType($user->id, 'owner_id');
	$user->shop = $shop;
	
	return response($user);
});

// them hoac chinh sua thong tin user
$app->post($container['administrator'].'/users', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("username", $params))  	return response(false);
	if (!array_key_exists("first_name", $params)) 	$params["first_name"] = false;
	if (!array_key_exists("last_name", $params))  	$params["last_name"] = false;
	if (!array_key_exists("email", $params)) 	 	return response(false);
	if (!array_key_exists("password", $params))  	return response(false);
	if (!array_key_exists("birthdate", $params)) 	$params["birthdate"] = false;
	if (!array_key_exists("gender", $params))  	 	$params["gender"] = false;
	if (!array_key_exists("mobilelogin", $params)) 	return response(false);


	$username = strtolower($params['username']);
	$mobilelogin = preg_replace("/^\\+?84/i", "0", $params['mobilelogin']);
	$email = $params['email'];

	$user = $userService->getUserByType($username, 'username', false);
	if ($user) return response([
		'status' => false,
		'error' => 'username_exist'
	]);

	$user = $userService->getUserByType($mobilelogin, 'mobilelogin', false);
	if ($user) return response([
		'status' => false,
		'error' => 'mobile_exist'
	]);

	$user = $userService->getUserByType($email, 'email', false);
	if ($user) return response([
		'status' => false,
		'error' => 'email_exist'
	]);

	$salt = substr(uniqid(), 5);
	$password = md5($params["password"] . $salt);

	$user_data = null;
	$user_data['type'] = 'normal';
	$user_data['username'] = $username;
	$user_data['email'] = $email;
	$user_data['password'] = $password;
	$user_data['salt'] = $salt;
	$user_data['first_name'] = $params['first_name'];
	$user_data['last_name'] = $params['last_name'];
	$user_data['time_created'] = time();
	$user_data['mobilelogin'] = $mobilelogin;
	$user_data['birthdate'] = $params['birthdate'];
	$user_data['gender'] = $params['gender'];
	$user_data['usercurrency'] = "VND";

	return response($userService->save($user_data));
	
});

// lay nhieu user
$app->put($container['administrator'].'/users', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('offset', $params)) $params['offset'] = 0;
	if (!array_key_exists('limit', $params)) $params['limit'] = 10;

	$users = $userService->getUsers(null, $params['offset'], $params['limit'], true);

	return response($users);
});