<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['administrator'].'/excel_products', function (Request $request, Response $response, array $args) {
	global $settings;
	$progressbarService = ProgressbarService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('shop_id', $params)) $params['shop_id'] = false;
	$files = $request->getUploadedFiles();
	$file = $files['file'];
	if ($file) {
		$filename = $file->getClientFilename();
		$code = md5(time().$filename);
		$progressbar_data['code'] = $code;
		$progressbar_data['inserted'] = 0;
		$progressbar_data['updated'] = 0;
		$progressbar_data['error'] = 0;
		$progressbar_data['number'] = 0;
		$progressbar_data['filename'] = $filename;
		$progressbar_data['creator_id'] = $loggedin_user->id;
		$progressbar_data['status'] = 0;
		if ($progressbarService->save($progressbar_data)) {
			$path = DIRECTORY_SEPARATOR."import".DIRECTORY_SEPARATOR."{$code}";
	        $dir = $settings['image']['path'].$path;
	        if (!file_exists($dir)) {
	            mkdir($dir, 0777, true);
	        }
	        $file->moveTo($dir . DIRECTORY_SEPARATOR . $filename);
	        return response(['code' => $code]);
		}
	}
	return response(false);

});