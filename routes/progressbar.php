<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/progressbar', function (Request $request, Response $response, array $args) {
	$progressbarService = ProgressbarService::getInstance();
	
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('code', $params)) return response(false);

	$progressbar = $progressbarService->getInfoByCode($params['code']);
	return response($progressbar);

})->setName('progressbar');

$app->post($container['prefix'].'/progressbar', function (Request $request, Response $response, array $args) {
	global $settings;
	$progressbarService = ProgressbarService::getInstance();
	$productService = ProductService::getInstance();
	
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('code', $params)) return response(false);

	$code = $params['code'];
	$progressbar = $progressbarService->getInfoByCode($code);
	if (!$progressbar) return false;
	if ($progressbar->status == 1) return response(false);

	$path = DIRECTORY_SEPARATOR."import".DIRECTORY_SEPARATOR."{$code}";
	$dir = $settings['image']['path'].$path;

	$tmpfname = $dir.DIRECTORY_SEPARATOR.$progressbar->filename;

	$excelReader = PHPExcel_IOFactory::createReaderForFile($tmpfname);
	$excelObj = $excelReader->load($tmpfname);
	$worksheet = $excelObj->getSheet(0);
	$lastRow = $worksheet->getHighestRow();

	$list_key_excel = $productService->excel_product_key();

	$number = $number_inserted = $number_updated = $number_error = 0;
	$inserted = $updated = $error = false;
	for ($row = 8; $row <= $lastRow; $row++) {
		$product_data = null;
		foreach ($list_key_excel as $key => $value) {
			$product_data[$value] = $worksheet->getCell($key.$row)->getValue();
		}
		$product_data['owner_id'] = $progressbar->owner_id;
		$product_data = $productService->product_conditions($product_data);

		if (!$product_data) {
			$number_error++;
			continue;
		}

		$product = $productService->checkSKUshop($product_data['sku'], $progressbar->owner_id);
		if ($product) {
			$updated = true;
			$product_data['id'] = $product->id;
		}
		if ($productService->save($product_data)) {
			if ($updated) {
				$number_updated++;
			} else {
				$number_inserted++;
			}
		} else {
			$number_error++;
		}

		$progressbarService->updateNumber($progressbar->id, $number_inserted, $number_updated, $number_error, $row);
	}

	return response(true);


})->setName('progressbar');