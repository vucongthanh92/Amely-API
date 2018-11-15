<?php
use Slim\Http\Request;
use Slim\Http\Response;


$app->post($container['administrator'].'/categories', function (Request $request, Response $response, array $args) {
	$imageService = ImageService::getInstance();
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

	$category_params = null;
	$uploadedFiles = $request->getUploadedFiles();

    // handle single input with single file upload
    $uploadedFile = $uploadedFiles['logo'];
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        $files = $request->getUploadedFiles();
        $file = $files['logo'];
		$category_params['logo'] = $files['logo']->getClientFilename();
		$filename = $category_params['logo'];
		$imageService->uploadImage(99999, 'test', 'images', $file, rand());
    }

    die('2131');
	$category_params['owner_id'] = $params['owner_id'];
	$category_params['type'] = $params['type'];
	$category_params['title'] = $params['title'];
	$category_params['description'] = $params['description'];
	$category_params['subtype'] = $params['subtype'];
	$category_params['friendly_url'] = $params['friendly_url'];
	$category_params['sort_order'] = 0;
	$category_params['enabled'] = 0;
	$category_params['parent_id'] = $params['parent_id'];
	$category_params['creator_id'] = $loggedin_user->id;
	

	return response($categoryService->save($category_params));
});