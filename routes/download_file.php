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
	
	$path = DIRECTORY_SEPARATOR."{$owner_type}".DIRECTORY_SEPARATOR."{$owner_id}".DIRECTORY_SEPARATOR."{$image_type}";
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
			$resize->saveImage(DIRECTORY_SEPARATOR."{$dir}".DIRECTORY_SEPARATOR."{$key}_{$filename}");
		}
		unlink($image);
	}

	$filenames = implode(',', $filenames);

	switch ($owner_type) {
		case 'feed':
			$feed = new Feed();
			$feed->data->images = $filenames;
			$feed->where = "id = {$owner_id}";
			return response($feed->update());
		case 'user':
			$user = new User();
			$user->id = $owner_id;
			$user->data->$image_type = $filenames;
			$user->where = "id = {$owner_id}";
			return response($user->update());
			break;
		case 'comment':
			$comment = new Annotation();
			$comment->id = $owner_id;
			$comment->data->images = $filenames;
			$comment->where = "id = {$owner_id}";
			return response($comment->update());
			break;
		case 'product':
			$product = new Product();
			$product->id =  $owner_id;
			$product->data->images = $filenames;
			$product->where = "id = {$owner_id}";
			break;
		default:
			# code...
			break;
	}
	return response(false);

})->setName('download_file');