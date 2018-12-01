<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/console_offers', function (Request $request, Response $response, array $args) {
	$offerService = OfferService::getInstance();

	$time = time();


	
	return response(true);
});