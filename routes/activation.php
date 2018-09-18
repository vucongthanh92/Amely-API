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

	if ($params['email']) {
		$user_params = null;
		$user_params[] = [
			'key' => 'email',
			'value' => "= '{$params['email']}'",
			'operation' => ''
		];
		$user = $select->getUsers($user_params,0,1,false);
		if ($user->verification_code == $params['code']) {
			if ($user->activation) {
				$object_tmp = new stdClass;
				$object_tmp->update->activation = "";
				$object_tmp->where = "guid= '{$guid}'";
				return $db->updateTable($object_tmp, 'ossn_users');
			}
			$object_tmp = new stdClass;
			$object_tmp->update->verification_code = "";
			$object_tmp->where = "guid= '{$guid}'";
			return $db->updateTable($object_tmp, 'ossn_users');
		}
		return response(false);
	}
	if ($params['mobilelogin']) {
		$user_params = null;
		$user_params[] = [
			'key' => 'username',
			'value' => "= '{$params['username']}'",
			'operation' => ''
		];
		$user = $select->getUsers($user_params,0,1,false);
		if ($user->mobilelogin == $params['mobilelogin']) {
			if ($user->verification_code == $params['code']) {
				if ($user->activation) {
					$object_tmp = new stdClass;
					$object_tmp->update->activation = "";
					$object_tmp->where = "guid= '{$guid}'";
					return $db->updateTable($object_tmp, 'ossn_users');
				}
				$object_tmp = new stdClass;
				$object_tmp->update->verification_code = "";
				$object_tmp->where = "guid= '{$guid}'";
				return $db->updateTable($object_tmp, 'ossn_users');
			}
			return response(false);
		}
		return response(false);
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

	if ($params['email']) {
		$user_params = null;
		$user_params[] = [
			'key' => 'email',
			'value' => "= '{$params['email']}'",
			'operation' => ''
		];
		$user = $select->getUsers($user_params,0,1,false);
		if (!$user) return false;
		$code = rand(100000, 999999);
		if ($services->sendByEmail($user->email, "AMELY", $code)) {
			$update = new stdClass;
			$update->verification_code = $code;

			$object_tmp = new stdClass;
			$object_tmp->update = $update;
			$object_tmp->where = "guid = '{$user->guid}'";
			if ($db->updateTable($object_tmp, 'ossn_users')) return response(true);
		}
		return response(false);
	}
	if ($params['mobilelogin']) {
		$user_params = null;
		$user_params[] = [
			'key' => 'mobilelogin',
			'value' => "= '{$params['mobilelogin']}'",
			'operation' => ''
		];
		$user = $select->getUsers($user_params,0,1,false);
		if (!$user) return false;
		$code = rand(100000, 999999);

		if ($services->sendByMobile($user->mobilelogin, $code)) {
			$update = new stdClass;
			$update->verification_code = $code;

			$object_tmp = new stdClass;
			$object_tmp->update = $update;
			$object_tmp->where = "guid = '{$guid}'";
			if ($db->updateTable($object_tmp, 'ossn_users')) return response(true);
		}
		return response(false);
	}
	return response(false);
})->setName('activation');