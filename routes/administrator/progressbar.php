<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['administrator'].'/progressbar', function (Request $request, Response $response, array $args) {
	$progressbarService = ProgressbarService::getInstance();
	
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('code', $params)) return response(false);

	$progressbar = $progressbarService->getInfoByCode($params['code']);
	return response($progressbar);

})->setName('progressbar');

$app->post($container['administrator'].'/progressbar', function (Request $request, Response $response, array $args) {
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
	$number = 0;
	$inserted = $updated = $error = false;
	$list_inserted = $list_updated = $list_error = [];

	if (!empty($progressbar->inserted)) {
		$list_inserted = explode('^0^', $progressbar->inserted);
	}
	if (!empty($progressbar->updated)) {
		$list_updated = explode('^0^', $progressbar->updated);
	}
	if (!empty($progressbar->error)) {
		$list_error = explode('^0^', $progressbar->error);
	}
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
		} else {
			$updated = false;
		}

		if ($productService->save($product_data)) {
			if ($updated) {
				$list_updated = array_merge($list_updated, [$product_data['sku']]);
			} else {
				$list_inserted = array_merge($list_inserted, [$product_data['sku']]);
			}
		} else {
			$list_error = array_merge($list_error, $product_data['sku']);
		}

		$progressbarService->updateNumber($progressbar->id, implode('^0^', $list_inserted), implode('^0^', $list_updated), implode('^0^', $list_error), $row);
	}

	return response(true);


})->setName('progressbar');