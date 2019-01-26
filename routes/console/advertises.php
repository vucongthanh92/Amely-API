<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/console_advertises', function (Request $request, Response $response, array $args) {
	$advertiseService = AdvertiseService::getInstance();
	$time = time();

	$ad_params[] = [
		'key' => '',
		'value' => "budget*1 < cpc*1",
		'operation' => ''
	];
	
	$ad_params[] = [
		'key' => 'approved',
		'value' => "> 0",
		'operation' => 'AND'
	];

	$ad_params[] = [
		'key' => 'status',
		'value' => "= 1",
		'operation' => 'AND'
	];

	$ad_params[] = [
		'key' => 'end_time*1',
		'value' => "<= {$time}",
		'operation' => 'OR'
	];

	$ads = $advertiseService->getAdvertises($ad_params, 0, 999999999);
	if ($ads) {
		foreach ($ads as $key => $ad) {
			$ad = object_cast("Advertise", $ad);
			$ad->data->status = 0;
			$ad->where = "id = {$ad->id}";
			$ad->update();
		}
	}
	return response(true);


})->setName('console_advertises');
