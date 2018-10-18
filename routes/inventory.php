<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/inventory', function (Request $request, Response $response, array $args) {
	$inventoryService = InventoryService::getInstance();
	$itemService = ItemService::getInstance();

	$snapshotService = SnapshotService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('type', $params)) 		$params['type'] = "user";
	if (!array_key_exists('owner_id', $params))	 	$params['owner_id'] = $loggedin_user->id;
	
	$type = $params['type'];
	$owner_id = $params['owner_id'];
	$inventory = $inventoryService->getInventoryByType($owner_id, $type);

	$count = [
		'new' => 0,
		'wishlist' => 0,
		'givelist' => 0,
		'expired' => 0,
		'expiry' => 0,
		'non_expiry' => 0,
		'voucher' => 0,
		'ticket' => 0,
		'stored' => 0,
		'nearly_expiry' => 0,
		'nearly_stored' => 0
	];
	
	$result['count'] = $count;
	$result['total_price'] = 0;
	$result['total_quantity'] = 0;

	if (!$inventory) return response($result);

	$time = time();
	$current_time_before_24hour = $time - (24*60*60);

	$item_params = null;
	$item_params[] = [
		'key' => 'owner_id',
		'value' => "= {$inventory->id}",
		'operation' => ''
	];
	$item_params[] = [
		'key' => 'type',
		'value' => "= 'inventory'",
		'operation' => 'AND'
	];
	$item_params[] = [
		'key' => 'status',
		'value' => "= 1",
		'operation' => 'AND'
	];
	$items = $itemService->getItems($item_params, 0, 9999999999);
	if (!$items) return response($result);

	$total_type = $total_price = $total_quantity = $new_count = $wishlist_count = $givelist_count = $expired_count = $expiry_count = $non_expiry_count = $voucher_count = $ticket_count = $stored_count = $nearly_expiry_count = $nearly_stored_count = 0;
	$products_snapshot = [];
	foreach ($items as $key => $item) {
		
		$total_quantity += $item->quantity;
		// new
		if ($item->time_created >= $current_time_before_24hour) {
			$new_count = $new_count + $item->quantity;
		}
		if ($item->stored_end >= $time AND $item->stored_end != 0 AND $item->stored_end != "") {
			// wishlist
			if ($item->wishlist == 2) {
				$wishlist_count = $wishlist_count + $item->quantity;
			}
			// givelist
			if ($item->givelist == 2) {
				$givelist_count = $givelist_count + $item->quantity;	
			}
			// expired
			if ($item->end_day < $time AND $item->end_day != 0 AND $item->end_day != "") {
				$expired_count = $expired_count + $item->quantity;
			}
			// expiry
			if ($item->expiry_type > 0 AND $item->end_day >= $time AND $item->end_day != 0 AND $item->end_day != "") {
				$expiry_count = $expiry_count + $item->quantity;
			}
			// non_expiry
			if ($item->expiry_type == 0) {
				$non_expiry_count = $non_expiry_count + $item->quantity;
			}
			// voucher
			if ($item->is_special == 1) {
				$voucher_count = $voucher_count + $item->quantity;
			}
			// ticket
			if ($item->is_special == 2) {
				$ticket_count = $ticket_count + $item->quantity;
			}
			// nearly_expiry
			if (($item->end_day - 259200) <= $time AND $item->end_day >= $time AND $item->end_day != 0 AND $item->end_day != "") {
				$nearly_expiry_count = $nearly_expiry_count + $item->quantity;
			}
			// nearly_stored
			if (($item->stored_end - 259200) <= $time) {
				$nearly_stored_count = $nearly_stored_count + $item->quantity;
			}
		}
		// stored
		if ($item->stored_end <= $time AND $item->stored_end != 0 AND $item->stored_end != "") {
			$stored_count = $stored_count + $item->quantity;
		}
		
		if (($item->stored_end*1 - 259200) <= $time && ($item->stored_end*1) >= $time && $item->stored_end != 0) {
			$item->nearly_stored_expried = true;
		}
		if ($time >= $item->stored_end) {
			$item->stored_expried = true;
		}
		if ($time >= $item->end_day && $item->end_day != 0) {
			$item->used = true;
		}
		$total_price += $item->price * $item->quantity;
	}

	$count = [
		'new' => $new_count,
		'wishlist' => $wishlist_count,
		'givelist' => $givelist_count,
		'expired' => $expired_count,
		'expiry' => $expiry_count,
		'non_expiry' => $non_expiry_count,
		'voucher' => $voucher_count,
		'ticket' => $ticket_count,
		'stored' => $stored_count,
		'nearly_expiry' => $nearly_expiry_count,
		'nearly_stored' => $nearly_stored_count
	];
	$result['count'] = $count;
	$result['total_price'] = $total_price;
	$result['total_quantity'] = $total_quantity;

	return response($result);

});

$app->post($container['prefix'].'/inventory', function (Request $request, Response $response, array $args) {

	$inventoryService = InventoryService::getInstance();
	$itemService = ItemService::getInstance();
	$snapshotService = SnapshotService::getInstance();

	$loggedin_user = loggedin_user();
	$current_time = time();
	$current_time_before_24hour = $current_time - (24*60*60);
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('item_type', $params))	$params['item_type'] = 'new';
	if (!array_key_exists('owner_id', $params))		$params['owner_id'] = $loggedin_user->id;
	if (!array_key_exists('type', $params))			$params['type'] = 'user';
	if (!array_key_exists('limit', $params))	 	$params['limit'] = 10;
	if (!array_key_exists('offset', $params))	 	$params['offset'] = 0;
	
	$inventory = $inventoryService->getInventoryByType($params['owner_id'], $params['type']);
	if (!$inventory) return response(false);
	$offset = (double)$params['offset'];
	$limit = (double)$params['limit'];
	$item_type = $params['item_type'];

	$item_params = null;
	$item_params[] = [
		'key' => "owner_id",
		'value' => "= {$inventory->id}",
		'operation' => ''
	];
	$item_params[] = [
		'key' => "type",
		'value' => "= 'inventory'",
		'operation' => 'AND'
	];
	$item_params[] = [
		'key' => "quantity",
		'value' => "> 0",
		'operation' => 'AND'
	];
	$item_params[] = [
		'key' => "snapshot_id",
		'value' => "<> ''",
		'operation' => 'AND'
	];
	$item_params[] = [
		'key' => 'status',
		'value' => "= 1",
		'operation' => 'AND'
	];
	$item_params[] = [
		'key' => "time_created",
		'value' => "DESC",
		'operation' => 'order_by'
	];
	
	switch ($item_type) {
		case 'all':

			break;
		// moi nhap
		case 'new':
			$item_params[] = [
				'key' => "time_created",
				'value' => ">= {$current_time_before_24hour}",
				'operation' => 'AND'
			];
            break;
        // danh muc yeu thich
		case 'wishlist':
			$item_params[] = [
				'key' => "wishlist",
				'value' => "= 2",
				'operation' => 'AND'
			];
			break;
		// cho di
		case 'givelist':
			$item_params[] = [
				'key' => "givelist",
				'value' => "= 2",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => "stored_end",
				'value' => ">= {$current_time}",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => "stored_end",
				'value' => "<> 0",
				'operation' => 'AND'
			];
			break;
		// het han dung
		case 'expired':
			$item_params[] = [
				'key' => "end_day",
				'value' => "< {$current_time}",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => "end_day",
				'value' => "<> 0",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => "stored_end",
				'value' => ">= {$current_time}",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => "stored_end",
				'value' => "<> 0",
				'operation' => 'AND'
			];
			break;
		// co han dung
		case 'expiry':
			$item_params[] = [
				'key' => "end_day",
				'value' => ">= {$current_time}",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => "end_day",
				'value' => "<> 0",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => "stored_end",
				'value' => ">= {$current_time}",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => "stored_end",
				'value' => "<> 0",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => "expiry_type",
				'value' => "> 0",
				'operation' => 'AND'
			];
			break;
		// khong han dung
		case 'non_expiry':
			$item_params[] = [
				'key' => "stored_end",
				'value' => ">= {$current_time}",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => "stored_end",
				'value' => "<> 0",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => "expiry_type",
				'value' => "= 0",
				'operation' => 'AND'
			];
			break;
		// voucher
		case 'voucher':
			$item_params[] = [
				'key' => "stored_end",
				'value' => ">= {$current_time}",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => "stored_end",
				'value' => "<> 0",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => "is_special",
				'value' => "= 1",
				'operation' => 'AND'
			];
			break;
		// ticket
		case 'ticket':
			$item_params[] = [
				'key' => "stored_end",
				'value' => ">= {$current_time}",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => "stored_end",
				'value' => "<> 0",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => "is_special",
				'value' => "= 2",
				'operation' => 'AND'
			];
			break;
		// het han luu kho
		case 'stored':
			$item_params[] = [
				'key' => "stored_end",
				'value' => "<= {$current_time}",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => "stored_end",
				'value' => "<> 0",
				'operation' => 'AND'
			];

			break;
		// gan het han su dung
		case 'nearly_expiry':
			$item_params[] = [
				'key' => 'AND',
				'value' => "(end_day - 259200) <= {$current_time}",
				'operation' => 'query_wheres'
			];
			$item_params[] = [
				'key' => 'end_day',
				'value' => ">= {$current_time}",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => 'end_day',
				'value' => "<> 0",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => "stored_end",
				'value' => ">= {$current_time}",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => "stored_end",
				'value' => "<> 0",
				'operation' => 'AND'
			];
			break;
		// gan het han luu kho
		case 'nearly_stored':
			$item_params[] = [
				'key' => 'AND',
				'value' => "(stored_end - 259200) <= {$current_time}",
				'operation' => 'query_wheres'
			];
			$item_params[] = [
				'key' => "stored_end",
				'value' => ">= {$current_time}",
				'operation' => 'AND'
			];
			$item_params[] = [
				'key' => "stored_end",
				'value' => "<> 0",
				'operation' => 'AND'
			];
			break;
		default:
			return response(false);
			break;
	}
	$items = $itemService->getItems($item_params, $offset, $limit);
	if (!$items) return response(false);
	$snapshots_id = [];
	foreach ($items as $key => $item) {
		array_push($snapshots_id, $item->snapshot_id);
		// $item->product_snapshot = $product_snapshot;
		if (($item->stored_end*1 - 259200) <= $current_time && ($item->stored_end*1) >= $current_time && $item->stored_end != 0) {
			$item->nearly_stored_expried = true;
		}
		if ($current_time >= $item->stored_end) {
			$item->stored_expried = true;
		}
		if ($current_time >= $item->end_day && $item->end_day != 0) {
			$item->used = true;
		}
	}
	$snapshots_id = implode(",", array_unique($snapshots_id));
	$snapshot_params = null;
	$snapshot_params[] = [
		'key' => 'id',
		'value' => "IN ($snapshots_id)",
		'operation' => ''
	];

	$snapshots = $snapshotService->getSnapshots($snapshot_params, 0, 99999999);
	if (!$snapshots) return response(false);
	foreach ($items as $key => $item) {
	  foreach ($snapshots as $snapshot) {
            if ($item->snapshot_id = $snapshot->id) {
                $item->snapshot = $snapshot;
            }
        }
	}
	return response(array_values($items));
});