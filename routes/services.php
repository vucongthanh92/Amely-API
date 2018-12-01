<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes
// $app->get('/authtoken', function (Request $request, Response $response, array $args) {
$app->get($container['prefix'].'/services', function (Request $request, Response $response, array $args) {
	$current_time = time();
	$siteSettingService = SiteSettingService::getInstance();
	$conditions = null;
	$conditions[] = [
		'key' => 'name',
		'value' => "IN ('android_version', 'ios_version', 'limit_offer', 'limit_gift')",
		'operation' => ''
	];

	$settings = $siteSettingService->getSiteSettings($conditions, 0, 99999999);
	$data['current_time'] = $current_time;
	foreach ($settings as $key => $setting) {
		$data[$setting->name] = $setting->value;
	}
	return response($data);
})->setName('services');

$app->post($container['prefix'].'/services', function (Request $request, Response $response, array $args) {
	global $elasticsearch;
// 	$params = [
// 	    'index' => 'my_index',
// 	    'body' => [
// 	        'settings' => [
// 	            'number_of_shards' => 3,
// 	            'number_of_replicas' => 2
// 	        ],
// 	        'mappings' => [
// 	            'my_type' => [
// 	                '_source' => [
// 	                    'enabled' => true
// 	                ],
// 	                'properties' => [
// 	                    'first_name' => [
// 	                        'type' => 'keyword',
// 	                        'analyzer' => 'standard'
// 	                    ],
// 	                    'age' => [
// 	                        'type' => 'integer'
// 	                    ]
// 	                ]
// 	            ]
// 	        ]
// 	    ]
// 	];


// 	// Create the index with mappings and settings now
// 	$response = $client->indices()->create($params);

// 	$params = null;
// 	$params = [
// 	    'index' => "test",
// 	    'type' => "test",
// 	    'id' => 1,
// 	    'body' => [
// 	    	'Title' => '',
// 	    	'Phone' => '',
// 	    	'Username' => '',
// 	    	'Fullname' => '',
// 	    	'Email' => '',
// 	    	'Price' => '',
// 	    	'Image' => '',
// 	    	'Shop'  => ''
// 	    ]
// 	];

// 	$elasticsearch->index($params);

// {
//   "mappings": {
//     "shop": {
//       "properties": {
//         "location": {
//           "type": "geo_point"
//         },
//         "Title": {
//           "type": "string"
//         },
//         "Description": {
//           "type": "string"
//         },
//         "id": {
//           "type": "string"
//         },
//         "Image": {
//           "type": "string"
//         },
//         "Phone": {
//           "type": "string"
//         },
//         "Address": {
//           "type": "string"
//         },
//         "OwnerID": {
//           "type": "string"
//         }
//       }
//     }
//   }
// }



	die('2131231');
	// $imageService = ImageService::getInstance();
	// $uploadedFile = $uploadedFiles['logo'];
 //    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
 //        $files = $request->getUploadedFiles();
 //        $file = $files['logo'];
	// 	$category_params['logo'] = $files['logo']->getClientFilename();
	// 	$filename = $category_params['logo'];
	// 	$filename = rand();
	// 	$imageService->uploadImage(99999, 'test', 'images', $file, $filename);
	// 	return response($filename);
 //    }

$order = <<<HTTP_BODY
{
    "products": [{
        "name": "bút",
        "weight": 0.1,
        "quantity": 1
    }, {
        "name": "tẩy",
        "weight": 0.2,
        "quantity": 1
    }],
    "order": {
        "id": "a4",
        "pick_name": "HCM-nội thành",
        "pick_address": "590 CMT8 P.11",
        "pick_province": "TP. Hồ Chí Minh",
        "pick_district": "Quận 3",
        "pick_tel": "0911222333",
        "tel": "0911222333",
        "name": "GHTK - HCM - Noi Thanh",
        "address": "123 nguyễn chí thanh",
        "province": "TP. Hồ Chí Minh",
        "district": "Quận 1",
        "is_freeship": "1",
        "pick_date": "2016-09-30",
        "pick_money": 47000,
        "note": "Khối lượng tính cước tối đa: 1.00 kg",
        "value": 3000000,
        "transport": "fly",
    }
}
HTTP_BODY;

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => "https://services.giaohangtietkiem.vn/services/shipment/order",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $order,
    CURLOPT_HTTPHEADER => array(
        "Content-Type: application/json",
        "Token: DDC3c40bB1C218c35a45929D64F66cE44c415785",
        "Content-Length: " . strlen($order),
    ),
));

$response = curl_exec($curl);
curl_close($curl);

echo 'Response: ' . $response;


    // return response(false);

})->setName('services');


$app->patch($container['prefix'].'/services', function (Request $request, Response $response, array $args) {
	$services = Services::getInstance();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('notify_token', $params))	 	$params['notify_token'] = 0;
	if (!array_key_exists('title', $params))	 	$params['title'] = "";
	if (!array_key_exists('body', $params))	 	$params['body'] = "";
	if (!array_key_exists('data', $params))	 	$params['data'] = "";

	$obj = new stdClass;
	$obj->token = $params['notify_token'];
	$obj->title = $params['title'];
	$obj->body = $params['body'];
	$obj->collapse_key = "green";
	$data = new stdClass;
	$obj->data = $data;

	return response($services->connectServer("notify", $obj));


	// $from = new stdClass;
	// $from->username = "thinhn1";
	// $to = new stdClass;
	// $to->username = "thinhn0";
	// $obj = new stdClass;
	// $obj->from = $from;
	// $obj->to = $to;
	// return response($services->connectServer("addFriend", $obj));

	// $member = new stdClass;
	// $member->username = "thinhn1";
	// $obj = new stdClass;
	// $obj->type = 'delete';
	// $obj->member = $member;
	// $obj->group_id = 23;
	// return response($services->connectServer("memberGroup", $obj));

	// $owner = new stdClass;
	// $owner->username = "thinhn0";
	// $obj = new stdClass;
	// $obj->owner = $owner;
	// $obj->group_id = 23;
	// $obj->title = "Name of group 23";
	// return response($services->connectServer("createGroup", $obj));
})->setName('services');