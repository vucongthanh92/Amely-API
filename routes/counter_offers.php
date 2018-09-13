<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/counter_offers', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	return response(false);
});