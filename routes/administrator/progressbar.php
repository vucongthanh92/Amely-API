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

	$objPHPExcel = new \PHPExcel();

	$path = DIRECTORY_SEPARATOR."import".DIRECTORY_SEPARATOR."{$code}";
	$dir = $settings['image']['path'].$path;

	$tmpfname = $dir.DIRECTORY_SEPARATOR.$progressbar->filename;

	$excelReader = PHPExcel_IOFactory::createReaderForFile($tmpfname);
	$excelObj = $excelReader->load($tmpfname);
	$worksheet = $excelObj->getSheet(0);
	$lastRow = $worksheet->getHighestRow();

	$list_key_excel = $productService->excel_product_key();

	for ($row = 10; $row <= $lastRow; $row++) {
		$product_data = null;
		foreach ($list_key_excel as $key => $value) {
			$product_data[$value] = $worksheet->getCell($key.$row)->getValue();
		}
		var_dump($product_data);
		die('1');
	}
	var_dump($worksheet);
	var_dump($lastRow);
	die();

	echo "<table>";
	for ($row = 1; $row <= $lastRow; $row++) {
		 echo "<tr><td>";
		 echo $worksheet->getCell('A'.$row)->getValue();
		 echo "</td><td>";
		 echo $worksheet->getCell('B'.$row)->getValue();
		 echo "</td><tr>";
	}

	var_dump($objPHPExcel);
	die();

})->setName('progressbar');