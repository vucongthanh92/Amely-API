<?php
use Slim\Http\Request;
use Slim\Http\Response;


$app->post($container['administrator'].'/categories', function (Request $request, Response $response, array $args) {
	$categoryService = CategoryService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('owner_id', $params)) $params['owner_id'] = 0;
	if (!array_key_exists('type', $params)) $params['type'] = 0;
	if (!array_key_exists('title', $params)) $params['title'] = 0;
	if (!array_key_exists('description', $params)) $params['description'] = 0;
	if (!array_key_exists('subtype', $params)) $params['subtype'] = 0;
	if (!array_key_exists('friendly_url', $params)) $params['friendly_url'] = "";
	if (!array_key_exists('sort_order', $params)) $params['sort_order'] = 0;
	if (!array_key_exists('enabled', $params)) $params['enabled'] = 1;
	if (!array_key_exists('parent_id', $params)) $params['parent_id'] = 0;
	if (!array_key_exists('creator_id', $params)) $params['creator_id'] = $loggedin_user->id;

	$category_data = null;
	$category_data['owner_id'] = $params['owner_id'];
	$category_data['type'] = $params['type'];
	$category_data['title'] = $params['title'];
	$category_data['description'] = $params['description'];
	$category_data['subtype'] = $params['subtype'];
	$category_data['friendly_url'] = $params['friendly_url'];
	$category_data['sort_order'] = 0;
	$category_data['enabled'] = 0;
	$category_data['parent_id'] = $params['parent_id'];
	$category_data['creator_id'] = $loggedin_user->id;
	
	$uploadedFiles = $request->getUploadedFiles();
    $logo = false;
    $uploadedFile = $uploadedFiles['logo'];
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        $files = $request->getUploadedFiles();
        $logo = $files['logo'];
        
    }
	return response($categoryService->save($category_data, $logo));
});