<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/shipping', function (Request $request, Response $response, array $args) {
	$itemService = ItemService::getInstance();
	$storeService = StoreService::getInstance();
	$snapshotService = SnapshotService::getInstance();
	$addressService = AddressService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('shipping_method', $params)) $params['shipping_method'] = false;
	if (!array_key_exists('item_id', $params)) $params['item_id'] = false;
	if (!array_key_exists('cart_id', $params)) $params['cart_id'] = false;
	if (!array_key_exists('shipping_province', $params)) $params['shipping_province'] = false;
	if (!array_key_exists('shipping_district', $params)) $params['shipping_district'] = false;
	if (!array_key_exists('shipping_ward', $params)) $params['shipping_ward'] = false;
	if (!array_key_exists('shipping_address', $params)) $params['shipping_address'] = false;

	$fee_data = [];
	$shipping_province = $addressService->getAddress($params['shipping_province'], 'province');
	$shipping_district = $addressService->getAddress($params['shipping_district'], 'district');
	$shipping_ward = $addressService->getAddress($params['shipping_ward'], 'ward');
    $shipping_province_name = $shipping_province->name;
    $shipping_district_name = $shipping_district->name;
    $shipping_ward_name = $shipping_ward->name;

	$fee_data['province'] = $shipping_province_name;
	$fee_data['district'] = $shipping_district_name;
	$fee_data['ward'] = $shipping_ward_name;
	$fee_data['address'] = $params['shipping_address'];


	if ($params['item_id']) {
		$item = $itemService->getItemByType($params['item_id'], 'id');
		$snapshot = $snapshotService->getSnapshotByType($item->snapshot_id, 'id');
		$store = $storeService->getStoreByType($item->store_id, 'id');
		
		$fee_data['pick_province'] = $store->store_province_name;
		$fee_data['pick_district'] = $store->store_district_name;
		$fee_data['address'] = $store->store_address;
		$fee_data['weight'] = $snapshot->weight * $item->quantity;
		$fee_data['total'] = $snapshot->display_price * $item->quantity;

		$shippingService = ShippingService::getInstance();
		$sm = $shippingService->getMethod($params['shipping_method']);
		$shipping = $sm->checkFee($fee_data);
		if (!$shipping->fee->delivery) {
			return response([
				"status" => false,
				"error" => "{$params['item_id']}"
			]);
		}
		return response(["fee" => $shipping->fee->fee]);
	}

	if ($params['cart_id']) {
		$productService = ProductService::getInstance();
		$cartService = CartService::getInstance();
		$storeService = StoreService::getInstance();
		$cart = $cartService->getCartByType($params['cart_id'], 'id');
		$cart_items = $cartService->getCartItems($params['cart_id']);
		if ($cart->status == 1) return response(false);

		$stores = [];
		foreach ($cart_items as $key => $cart_item) {
			$product = $productService->getProductByType($cart_item->product_id, 'id');
			if ($product->snapshot_id != $cart_item->snapshot_id) return response(false);
			$store = $storeService->getStoreByType($cart_item->store_id, 'id');
			if (!$store) return response(false);
			$stores[$store->id]['province'] = $store->store_province_name;
			$stores[$store->id]['district'] = $store->store_district_name;
			$stores[$store->id]['address'] = $store->store_address;
			$stores[$store->id]['weight'] += $product->weight * $cart_item->quantity;
			$stores[$store->id]['total'] += $product->display_price * $cart_item->quantity;
			$stores[$store->id]['products'][] = $product->id;
		}

		$erro = 0;
		$str = [];
		$total_fee = 0;
		foreach ($stores as $key => $store) {
			$fee_data['pick_province'] = $store['province'];
			$fee_data['pick_district'] = $store['district'];
			$fee_data['address'] = $store['address'];
			$fee_data['weight'] = $store['weight'];
			$fee_data['total'] = $store['total'];

			$shippingService = ShippingService::getInstance();
			$sm = $shippingService->getMethod($params['shipping_method']);
			$shipping = $sm->checkFee($fee_data);

			if (!$shipping->fee->delivery) {
				$error += 1;
				foreach ($store[$key]['products'] as $product_id) {
					array_push($str, $product_id);
				}
			} else {
				$total_fee += $shipping->fee->fee;
			}
		}
		if ($erro == 0) {
			return response(["fee" => $total_fee]);
		} else {
			$str = implode(',', $str);
			return response([
				"status" => false,
				"error" => "{$str}"
			]);
		}
	}

	return response(false);
});