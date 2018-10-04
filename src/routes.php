<?php

use Slim\Http\Request;
use Slim\Http\Response;

function getDirContents($dir, &$results = array()){
    $files = scandir($dir);
    $files = array_diff($files, array('.', '..', 'Object.php', 'Services.php'));
    foreach($files as $key => $value){
        $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
        if(is_file($path)) {
            $results[] = $path;
        } else {
            getDirContents($path, $results);
        }
    }

    return $results;
}
require __DIR__ . '/../classes/bol/Object.php';
require __DIR__ . '/../classes/services/Services.php';

$classes = getDirContents(__DIR__ . '/../classes');

foreach ($classes as $key => $class) {

    require $class;
}

$routes = getDirContents(__DIR__ . '/../routes');

$shippingService = ShippingService::getInstance();
$shippingService->registerMethod([
    'filename' => 'sq/storage',
    'component' => 'Amely',
    'classname' => 'SQ\\Storage',
    'displayname' => "Kho của tôi",
    'capacity' => ['process', 'redeem']
]);

$shippingService->registerMethod([
    'filename' => 'sq/pickup',
    'component' => 'Amely',
    'classname' => 'SQ\\Pickup',
    'displayname' => "Nhận tại cửa hàng",
    'capacity' => ['redeem']
]);

$shippingService->registerMethod([
    'filename' => 'sq/express',
    'component' => 'Amely',
    'classname' => 'SQ\\Express',
    'displayname' => "Giao hàng tiết kiệm",
    'capacity' => ['process']
]);

$paymentsService = PaymentsService::getInstance();
$paymentsService->registerMethod([
    'filename' => 'paypal/standard',
    'component' => 'Amely',
    'classname' => 'Paypal\\Standard',
    'displayname' => "Tài khoản Paypal",
    'capacity' => ['process','deposit','withdraw']
]);

$paymentsService->registerMethod([
    'filename' => 'onepay/opatm',
    'component' => 'Amely',
    'classname' => 'OnePay\\OPATM',
    'displayname' => "OnePay thẻ ATM nội địa",
    'capacity' => ['process','deposit']
]);

$paymentsService->registerMethod([
    'filename' => 'onepay/opcreditcard',
    'component' => 'Amely',
    'classname' => 'OnePay\\OPCreditCard',
    'displayname' => "OnePay thẻ quốc tế Visa/Master",
    'capacity' => ['process','deposit']
]);

foreach ($routes as $key => $route) {
    require $route;
}



// die();

// // Routes
// require __DIR__ . '/../routes/authentication/login.php';
// require __DIR__ . '/../routes/profile.php';
// require __DIR__ . '/../routes/services.php';
// require __DIR__ . '/../routes/friends.php';
// require __DIR__ . '/../routes/product_group.php';
// require __DIR__ . '/../routes/feeds.php';
// require __DIR__ . '/../routes/banner.php';
// require __DIR__ . '/../routes/categories.php';
// require __DIR__ . '/../routes/featured_shops.php';
// require __DIR__ . '/../routes/featured_products.php';
// require __DIR__ . '/../routes/most_sold_products.php';




// $app->get('/users', function (Request $request, Response $response, array $args) {
// 	// $table = "ossn_users";
// 	// $conditions = null;
// 	// $conditions[] = [
// 	// 	'key' => 'guid',
// 	// 	'value' => 'DESC',
// 	// 	'operation' => 'order_by'
// 	// ];
// 	// $users = getData($this->db, $table, $conditions, $offset = 0, $limit = 10, $load_more = true);

// 	$en = new stdClass();
// 	$en->abc = "1111111";
// 	$en->def = "222222";
	

// 	$test = new stdClass();
// 	$test->guid = 12589;
// 	$test->owner_guid = 27;
// 	$test->type = "object";
// 	$test->subtype = "test";
// 	$test->data = $en;
// 	return response(updateEAV($this->db, $test));
// die('2131');
// 	return response(insertEAV($this->db, $test));
// 	// return response($users, $response);
// });