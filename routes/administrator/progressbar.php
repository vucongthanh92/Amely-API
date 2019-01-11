<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['administrator'].'/progressbar', function (Request $request, Response $response, array $args) {
	global $settings;
	$progressbarService = ProgressbarService::getInstance();
	$productService = ProductService::getInstance();
	
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('code', $params)) return response(false);
	$code = $params['code'];
	$progressbar = $progressbarService->getInfoByCode($code);

	if (!$progressbar) return false;
	if ($progressbar->status == 1) return response($progressbar);
	if ($progressbar->row >= $progressbar->total_number) return response($progressbar);
	// $sku_before = $sku_after = null;
	// $row = $progressbar->number + 1;

	// $path = DIRECTORY_SEPARATOR."import".DIRECTORY_SEPARATOR."{$code}";
	// $dir = $settings['image']['path'].$path;

	// $tmpfname = $dir.DIRECTORY_SEPARATOR.$progressbar->filename;

	// $excelReader = PHPExcel_IOFactory::createReaderForFile($tmpfname);
	// $excelObj = $excelReader->load($tmpfname);
	// $worksheet = $excelObj->getSheet(0);
	// $lastRow = $worksheet->getHighestRow();

	// $list_key_excel = $productService->excel_product_key();
	// $number = 0;
	// $inserted = $updated = $error = false;
	// $list_inserted = $list_updated = $list_error = [];

	// if (!empty($progressbar->inserted)) {
	// 	$list_inserted = explode('^0^', $progressbar->inserted);
	// }
	// if (!empty($progressbar->updated)) {
	// 	$list_updated = explode('^0^', $progressbar->updated);
	// }
	// if (!empty($progressbar->error)) {
	// 	$list_error = explode('^0^', $progressbar->error);
	// }
	// $checkError = 0;
	// $product_params = null;
	// foreach ($list_key_excel as $key => $value) {
	// 	$sku_before = $worksheet->getCell('D'.($row-1))->getValue();
	// 	$sku_after = $worksheet->getCell('D'.($row))->getValue();
	// 	$product_params[$value] = $worksheet->getCell($key.$row)->getValue();
	// }
	// if ($sku_before == null && $sku_after == null) {
	// 	$progressbarService->updateNumber($progressbar->id, implode('^0^', $list_inserted), implode('^0^', $list_updated), implode('^0^', $list_error), $lastRow, 1);
	// 	return response($progressbar);
	// }
	// $product_params['owner_id'] = $progressbar->owner_id;
	
	// $product_data = $productService->product_conditions($product_params);
	// if (!$product_data['status']) {
	// 	$list_error = array_merge($list_error, $product_data['data']['sku']);
	// 	$progressbarService->updateNumber($progressbar->id, implode('^0^', $list_inserted), implode('^0^', $list_updated), implode('^0^', $list_error), $row, 0);
	// 	return response($progressbar);
	// }

	// $product = $productService->checkSKUshop($product_data['data']['sku'], $progressbar->owner_id);
	// if ($product) {
	// 	$updated = true;
	// 	$product_data['data']['id'] = $product->id;
	// } else {
	// 	$updated = false;
	// }
	// $product_data['data']['status'] = 0;
	// if ($productService->save($product_data['data'])) {
	// 	if ($updated) {
	// 		$list_updated = array_merge($list_updated, [$product_data['data']['sku']]);
	// 	} else {
	// 		$list_inserted = array_merge($list_inserted, [$product_data['data']['sku']]);
	// 	}
	// } else {
	// 	$list_error = array_merge($list_error, $product_data['data']['sku']);
	// }
	// $status = 0;
	// if ($row >= $lastRow) {
	// 	$status = 1;
	// }
	// $progressbarService->updateNumber($progressbar->id, implode('^0^', $list_inserted), implode('^0^', $list_updated), implode('^0^', $list_error), $row, $status);


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
	$checkError = 0;
	for ($row = 7; $row <= $lastRow; $row++) {
		$product_data = null;
		foreach ($list_key_excel as $key => $value) {
			$product_data[$value] = $worksheet->getCell($key.$row)->getValue();
		}
		$product_data['owner_id'] = $progressbar->owner_id;

		$product = $productService->checkSKUshop($product_data['sku'], $progressbar->owner_id);
		if ($product) {
			$updated = true;
			$product_data['id'] = $product->id;
		} else {
			$product_data['id'] = false;
			$updated = false;
		}

		$product_conditions = $productService->product_conditions($product_data);
		$product_conditions['data']['creator_id'] = $progressbar->creator_id;
		
		if (!$product_conditions['status']) {
			$list_error = array_merge($list_error, $product_conditions['data']['sku']);

			$status = 0;
			if ($row >= $lastRow) {
				$status = 1;
			}
			$progressbarService->updateNumber($progressbar->id, implode('^0^', $list_inserted), implode('^0^', $list_updated), implode('^0^', $list_error), $row, $status);
			
			continue;
		}

		$product_conditions['data']['status'] = 0;

		if ($productService->saveByExcel($product_conditions['data'])) {
			if ($updated) {
				$list_updated = array_merge($list_updated, [$product_conditions['data']['sku']]);
			} else {
				$list_inserted = array_merge($list_inserted, [$product_conditions['data']['sku']]);
			}
		} else {
			$list_error = array_merge($list_error, $product_conditions['data']['sku']);
		}
		$status = 0;
		if ($row >= $lastRow) {
			$status = 1;
		}
		$progressbarService->updateNumber($progressbar->id, implode('^0^', $list_inserted), implode('^0^', $list_updated), implode('^0^', $list_error), $row, $status);
		continue;
	}

	return response(true);


})->setName('progressbar');

$app->put($container['administrator'].'/progressbar', function (Request $request, Response $response, array $args) {
	global $settings;
	$progressbarService = ProgressbarService::getInstance();
	$productService = ProductService::getInstance();
	
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('shop_id', $params)) return response(false);
	if (!array_key_exists('progress_type', $params)) return response(false);

	$conditions[] = [
		'key' => 'owner_id',
		'value' => "= {$params['shop_id']}",
		'operation' => ''
	];
	$conditions[] = [
		'key' => 'type',
		'value' => "= 'shop'",
		'operation' => 'AND'
	];
	$conditions[] = [
		'key' => 'progress_type',
		'value' => "= '{$params['progress_type']}'",
		'operation' => 'AND'
	];
	$conditions[] = [
		'key' => 'status',
		'value' => "= 0",
		'operation' => 'AND'
	];

	$progress = $progressbarService->getProgress($conditions, 0, 99999999);

	return response($progress);
});

$app->patch($container['administrator'].'/progressbar', function (Request $request, Response $response, array $args) {
	global $settings;
	$progressbarService = ProgressbarService::getInstance();
	$productService = ProductService::getInstance();
	
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('code', $params)) return response(false);
	$code = $params['code'];
	$progressbar = $progressbarService->getInfoByCode($code);
	$progressbar = object_cast("Progressbar", $progressbar);
	$progressbar->data->status = 1;
	$progressbar->data->id = $progressbar->id;
	$progressbar->where = "id = {$progressbar->id}";
	$progressbar->update();
	return response($progressbar);

})->setName('progressbar');