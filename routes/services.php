<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes
// $app->get('/authtoken', function (Request $request, Response $response, array $args) {
$app->get($container['prefix'].'/services', function (Request $request, Response $response, array $args) {
	// $paymentsService = PaymentsService::getInstance();
	// $pm = $paymentsService->getMethod();
	// die('12313');
	

	$current_time = time();
	$siteSettingService = SiteSettingService::getInstance();
	$conditions = null;
	$conditions[] = [
		'key' => 'name',
		'value' => "IN ('android_version', 'ios_version', 'limit_offer', 'limit_gift')",
		'operation' => ''
	];

	$settings = $siteSettingService->getSiteSettings($conditions, 0, 99999999);
	$data['current_time'] = $current_time;
	foreach ($settings as $key => $setting) {
		$data[$setting->name] = $setting->value;
	}
	return response($data);
})->setName('services');

$app->patch($container['prefix'].'/services', function (Request $request, Response $response, array $args) {
	$services = Services::getInstance();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('notify_token', $params))	 	$params['notify_token'] = 0;
	if (!array_key_exists('title', $params))	 	$params['title'] = "";
	if (!array_key_exists('body', $params))	 	$params['body'] = "";
	if (!array_key_exists('data', $params))	 	$params['data'] = "";

	$obj = new stdClass;
	$obj->token = $params['notify_token'];
	$obj->title = $params['title'];
	$obj->body = $params['body'];
	$obj->collapse_key = "green";
	$data = new stdClass;
	$obj->data = $data;

	return response($services->connectServer("notify", $obj));


	// $from = new stdClass;
	// $from->username = "thinhn1";
	// $to = new stdClass;
	// $to->username = "thinhn0";
	// $obj = new stdClass;
	// $obj->from = $from;
	// $obj->to = $to;
	// return response($services->connectServer("addFriend", $obj));

	// $member = new stdClass;
	// $member->username = "thinhn1";
	// $obj = new stdClass;
	// $obj->type = 'delete';
	// $obj->member = $member;
	// $obj->group_id = 23;
	// return response($services->connectServer("memberGroup", $obj));

	// $owner = new stdClass;
	// $owner->username = "thinhn0";
	// $obj = new stdClass;
	// $obj->owner = $owner;
	// $obj->group_id = 23;
	// $obj->title = "Name of group 23";
	// return response($services->connectServer("createGroup", $obj));
})->setName('services');