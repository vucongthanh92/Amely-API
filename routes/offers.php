<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/offers', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	$params = $request->getParsedBody();
	$time = time();

	$loggedin_user = loggedin_user();
	if ($loggedin_user->usercurrency)
		$currency_code = $loggedin_user->usercurrency;

	if (!array_key_exists('location_lat', $params)) $params['location_lat'] = false;
	if (!array_key_exists('location_lng', $params)) $params['location_lng'] = false;
	if (!array_key_exists('offset', $params)) 		$params['offset'] = 0;
	if (!array_key_exists('limit', $params)) 		$params['limit'] = 10;
	if (!array_key_exists('target', $params)) 		$params['target'] = false;
	
	$offer_params = null;
	$offer_params[] = [
		'key' => 'status',
		'value' => "= 'open'",
		'operation' => ''
	];
	switch ($params['target']) {
		case 'public':

			break;
		case 'location':

			break;
		case 'friends':

			break;
		
		
		default:
			$offer_params[] = [
				'key' => 'owner_guid',
				'value' => "= {$loggedin_user->guid}",
				'operation' => 'AND'
			];
			$offer_params[] = [
				'key' => 'quantity',
				'value' => ">= 1",
				'operation' => 'AND'
			];
			break;
	}

});