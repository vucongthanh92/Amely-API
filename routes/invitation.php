<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/invitation', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	return response(false);
});

$app->post($container['prefix'].'/invitation', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	return response(false);
});