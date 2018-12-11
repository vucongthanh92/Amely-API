<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['administrator'].'/excel_products', function (Request $request, Response $response, array $args) {
	global $settings;
	$progressbarService = ProgressbarService::getInstance();
	$shopService = ShopService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('shop_id', $params)) return response(false);

	$shop = $shopService->getShopByType($params['shop_id']);
	if (!$shop) return false;

	$files = $request->getUploadedFiles();
	$file = $files['file'];
	if ($file) {
		$filename = $file->getClientFilename();
		$code = md5(time().$filename);

		$path = DIRECTORY_SEPARATOR."import".DIRECTORY_SEPARATOR."{$code}";
        $dir = $settings['image']['path'].$path;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $tmpfname = $dir . DIRECTORY_SEPARATOR . $filename;
        $file->moveTo($tmpfname);

        $excelReader = PHPExcel_IOFactory::createReaderForFile($tmpfname);
		$excelObj = $excelReader->load($tmpfname);
		$worksheet = $excelObj->getSheet(0);
		$lastRow = $worksheet->getHighestRow();

		$progressbar_data['owner_id'] = $params['shop_id'];
		$progressbar_data['type'] = 'shop';
		$progressbar_data['code'] = $code;
		$progressbar_data['inserted'] = 0;
		$progressbar_data['updated'] = 0;
		$progressbar_data['error'] = 0;
		$progressbar_data['number'] = 0;
		$progressbar_data['total_number'] = $lastRow;
		$progressbar_data['filename'] = $filename;
		$progressbar_data['creator_id'] = $loggedin_user->id;
		$progressbar_data['status'] = 0;
		if ($progressbarService->save($progressbar_data)) {
	        return response(['code' => $code]);
		}
	}
	return response(false);

});