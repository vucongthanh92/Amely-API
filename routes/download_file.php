<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/download_file', function (Request $request, Response $response, array $args) {
	global $settings;
	$services = Services::getInstance();
	$imageService = ImageService::getInstance();

	$params = $request->getParsedBody();
	if (!$params) $params = [];

	if (!array_key_exists('owner_id', $params)) return response(false);
	if (!array_key_exists('owner_type', $params)) return response(false);
	if (!array_key_exists('image_type', $params)) return response(false);
	if (!array_key_exists('images', $params)) return response(false);
	$owner_id = $params['owner_id'];
	$owner_type = $params['owner_type'];
	$image_type = $params['image_type'];
	$urls = $params['images'];

	if (!in_array($image_type, ['avatar','cover','images'])) return response(false);
	
	$path = "/{$owner_type}/{$owner_id}/{$image_type}";
	$dir = $settings['image']['path'].$path;
	if (!file_exists($dir)) {
		mkdir($dir, 0777, true);
	}
	array_map('unlink', array_filter((array) glob("{$dir}/*")));

	$filenames = [];

	foreach ($urls as $key => $url) {
		$filename = md5($url . rand() . time()) . '.jpg';
		array_push($filenames, $filename);
		$image = $dir.'/'.$filename;

		file_put_contents($image, fopen($url, 'r'));

		$sizes = $imageService->image_sizes();
		foreach ($sizes as $key => $size) {
			$resize = new ResizeImage($image);
			$resize->resizeTo($size, $size, 'maxWidth');
			$resize->saveImage("/{$dir}/{$key}_{$filename}");
		}
		unlink($image);
	}

	$filenames = implode(',', $filenames);

	switch ($owner_type) {
		case 'user':
			$user = new User();
			$user->id = $owner_id;
			$user->data->$image_type = $filenames;
			return response($user->update());
			break;
		case 'comment':
			$comment = new Annotation();
			$comment->id = $owner_id;
			$comment->data->images = $filenames;
			return response($comment->update());
			break;
		default:
			# code...
			break;
	}

})->setName('download_file');