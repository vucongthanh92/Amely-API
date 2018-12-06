<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/console_gifts', function (Request $request, Response $response, array $args) {
	$giftService = GiftService::getInstance();
	$itemService = ItemService::getInstance();
	$time = time();

	$time_check = strtotime("-6 hours", $time);

	$gift_params = null;
	$gift_params[] = [
		'key' => 'time_created',
		'value' => "< {$time_check}",
		'operation' => ''
	];

	$gift_params[] = [
		'key' => 'status',
		'value' => "= 0",
		'operation' => 'AND'
	];

	$gifts = $giftService->getGifts($gift_params, 0, 99999999999);

	foreach ($gifts as $key => $gift) {
		$itemService->updateStatus($gift->item_id, 1);
		$gift = object_cast("Gift", $gift);
		$gift->data->status = 2;
		$gift->data->id = $gift->id;
		$gift->where = "id = {$gift->id}";
		$gift->update();
	}

	return response(true);


})->setName('console_gifts');
