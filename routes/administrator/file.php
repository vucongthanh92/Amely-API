<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['administrator'].'/file', function (Request $request, Response $response, array $args) {
	$productGroupService = ProductGroupService::getInstance();
	$categoryService = CategoryService::getInstance();

	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('shop_id', $params)) return responseError(ERROR_0);

	// $loggedin_user = loggedin_user();

	$categories_market = $categories_shop = [];

	$category_market_parent_params[] = [
		'key' => 'owner_id',
		'value' => "= 1",
		'operation' => ''
	];
	$category_market_parent_params[] = [
		'key' => 'type',
		'value' => "= 'AMELY'",
		'operation' => 'AND'
	];
	$category_market_parent_params[] = [
		'key' => 'parent_id',
		'value' => "= ''",
		'operation' => 'AND'
	];
	$category_market_parent_params[] = [
		'key' => 'enabled',
		'value' => "= '1'",
		'operation' => 'AND'
	];
	$category_market_parent_params[] = [
		'key' => 'sort_order',
		'value' => "DESC",
		'operation' => 'order_by'
	];
	$categories_market_parent = $categoryService->getCategories($category_market_parent_params, 0, 999999999);
	if ($categories_market_parent) {
		foreach ($categories_market_parent as $category_market_parent) {
			switch ($category_market_parent->subtype) {
				case 0:
					$category_market_parent->subtype = "hàng thường";
					break;
				case 1:
					$category_market_parent->subtype = "voucher";
					break;
				case 2:
					$category_market_parent->subtype = "ticket";
					break;
				default:
					$category_market_parent->subtype = "lỗi";
					break;
			}
			$category_market_parent->parent = $category_market_parent->title;
			$category_market_parent->child = "";
			array_push($categories_market, $category_market_parent);
			$category_market_child_params = null;
			$category_market_child_params[] = [
				'key' => 'parent_id',
				'value' => "= {$category_market_parent->id}",
				'operation' => ''
			];
			$category_market_child_params[] = [
				'key' => 'enabled',
				'value' => "= '1'",
				'operation' => 'AND'
			];
			$category_market_child_params[] = [
				'key' => 'sort_order',
				'value' => "DESC",
				'operation' => 'order_by'
			];
			$categories_market_child = $categoryService->getCategories($category_market_child_params, 0, 999999999);
			if ($categories_market_child) {
				foreach ($categories_market_child as $category_child) {
					switch ($category_child->subtype) {
						case 0:
							$category_child->subtype = "hàng thường";
							break;
						case 1:
							$category_child->subtype = "voucher";
							break;
						case 2:
							$category_child->subtype = "ticket";
							break;
						default:
							$category_child->subtype = "lỗi";
							break;
					}
					$category_child->parent = $category_market_parent->title;
					$category_child->child = $category_child->title;
					array_push($categories_market, $category_child);
				}
			}
		}
	}

	$category_shop_params[] = [
		'key' => 'owner_id',
		'value' => "= {$params['shop_id']}",
		'operation' => ''
	];
	$category_shop_params[] = [
		'key' => 'type',
		'value' => "= 'shop'",
		'operation' => 'AND'
	];
	$category_shop_params[] = [
		'key' => 'enabled',
		'value' => "= '1'",
		'operation' => 'AND'
	];
	$category_shop_params[] = [
		'key' => 'sort_order',
		'value' => "DESC",
		'operation' => 'order_by'
	];

	$categories_shop = $categoryService->getCategories($category_shop_params, 0, 999999999);

	$dir = $settings['root'];
	$file_default = $dir.'files'.DIRECTORY_SEPARATOR.'template.xlsx';
	$file_excel = $dir.'files'.DIRECTORY_SEPARATOR.'shop'.DIRECTORY_SEPARATOR.$params['shop_id'].DIRECTORY_SEPARATOR.'template.xlsx';

	if (!file_exists($dir.'files'.DIRECTORY_SEPARATOR.'shop'.DIRECTORY_SEPARATOR.$params['shop_id'])) {
        mkdir($dir.'files'.DIRECTORY_SEPARATOR.'shop'.DIRECTORY_SEPARATOR.$params['shop_id'], 0777, true);
    }

	if (file_exists($file_default)) {
		$pg_params[] = [
			'key' => 'status',
			'value' => "= 1",
			'operation' => ''
		];
		$pg_params[] = [
			'key' => 'id',
			'value' => "asc",
			'operation' => 'order_by'
		];
		$pgs = $productGroupService->getProductGroups($pg_params, 0, 9999999);

		$objFile = PHPExcel_IOFactory::identify($file_default);
		$objData = PHPExcel_IOFactory::createReader($objFile);
		$objPHPExcel = $objData->load($file_default);

		$sheetPG = $objPHPExcel->setActiveSheetIndex(1);
		$pg_number = 4;
		foreach ($pgs as $key => $pg) {
			$sheetPG->setCellValue('A'.$pg_number, $pg->id);
			$sheetPG->setCellValue('B'.$pg_number, $pg->title);
			$pg_number++;
		}

		$sheetMarket = $objPHPExcel->setActiveSheetIndex(2);
		$market_number = 3;
		foreach ($categories_market as $key => $category_market) {
			$sheetMarket->setCellValue('A'.$market_number, $category_market->id);
			$sheetMarket->setCellValue('B'.$market_number, $category_market->parent);
			$sheetMarket->setCellValue('C'.$market_number, $category_market->child);
			$sheetMarket->setCellValue('D'.$market_number, $category_market->subtype);
			$market_number++;
		}

		$sheetMarket = $objPHPExcel->setActiveSheetIndex(3);
		$shop_number = 3;
		foreach ($categories_shop as $key => $category_shop) {
			$sheetMarket->setCellValue('A'.$shop_number, $category_shop->id);
			$sheetMarket->setCellValue('B'.$shop_number, $category_shop->title);
			$shop_number++;
		}

		header('Content-type: application/vnd.ms-excel');
		header('Content-Disposition: attachment; filename="template.xlsx"');
		PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007')->save($file_excel);

		readfile($file_excel);
	}
})->setName('file');