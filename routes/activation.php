<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/activation', function (Request $request, Response $response, array $args) {
	$db = SlimDatabase::getInstance();
	$select = SlimSelect::getInstance();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("username", $params))  	$params["username"] = false;
	if (!array_key_exists("password", $params))  	$params["password"] = false;
	if (!array_key_exists("code", $params))  	$params["code"] = false;
	if (!array_key_exists("email", $params))  	$params["email"] = false;
	if (!array_key_exists("mobilelogin", $params))  	$params["mobilelogin"] = false;

	$update = new stdClass;
	$object = new stdClass;
	$user_params = null;

	if ($params['email']) {
		$user_params[] = [
			'key' => 'email',
			'value' => "= '{$params['email']}'",
			'operation' => ''
		];
	}
	if ($params['mobilelogin']) {
		$user_params[] = [
			'key' => 'mobilelogin',
			'value' => "= '{$params['mobilelogin']}'",
			'operation' => ''
		];
	}
	if (!$user_params) return response(false);

	$user = $select->getUsers($user_params, 0, 1, false, false);
	if ($user->verification_code == $params['code']) {
		if ($user->activation) {
			$update->activation = '';
		}
		$update->verification_code = '';
		$object->where = "id = {$user->id}";
		$object->update = $update;
		return response($db->saveTable($object, 'amely_users', 'update', false));
	}
	return response(false);
})->setName('activation');

$app->put($container['prefix'].'/activation', function (Request $request, Response $response, array $args) {

	$services = Services::getInstance();
	$db = SlimDatabase::getInstance();
	$select = SlimSelect::getInstance();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("email", $params))  		$params["email"] = false;
	if (!array_key_exists("mobilelogin", $params))  $params["mobilelogin"] = false;

	$update = new stdClass;
	$object = new stdClass;
	$user_params = null;

	if ($params['email']) {
		$user_params[] = [
			'key' => 'email',
			'value' => "= '{$params['email']}'",
			'operation' => ''
		];
	}
	if ($params['mobilelogin']) {
		$user_params[] = [
			'key' => 'mobilelogin',
			'value' => "= '{$params['mobilelogin']}'",
			'operation' => ''
		];
	}
	$user = $select->getUsers($user_params,0,1,false);
	if (!$user) return false;
	$code = rand(100000, 999999);

	$update = new stdClass;
	$object = new stdClass;
	$update->verification_code = $code;
	$object->update = $update;
	$object->where = "id = {$user->id}";

	if ($user) {
		if ($params['email']) {
			if ($services->sendByEmail($user->email, "AMELY", $code)) {
				return response($db->saveTable($object, 'amely_users', 'update', false));
			}
		}
		if ($params['mobilelogin']) {
			$mobilelogin = preg_replace("/^\\+?84/i", "0", $user->mobilelogin);
			if ($services->sendByMobile($mobilelogin, $code)) {
				return response($db->saveTable($object, 'amely_users', 'update', false));
			}
		}
	}
	return response(false);
})->setName('activation');