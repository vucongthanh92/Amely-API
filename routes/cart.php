<?php
use Slim\Http\Request;
use Slim\Http\Response;

// chua tru store

$app->get($container['prefix'].'/cart', function (Request $request, Response $response, array $args) {
	$cartService = CartService::getInstance();
	$productService = ProductService::getInstance();
	$storeService = StoreService::getInstance();
	$loggedin_user = loggedin_user();
	$carts['cart'] = [];
	$carts['items'] = [];
	$carts['tax'] = 0;
	$carts['total'] = 0;
	$carts['quantity'] = 0;
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('code', $params))  			$params['code'] = false;
	if (!array_key_exists('type', $params))  			$params['type'] = 'user';
	
	if ($params['code']) {
		$itemService = ItemService::getInstance();
		$services = Services::getInstance();
		$decrypt = $services->b64decode($params['code']);
		$data = $services->decrypt($decrypt);
		$data = unserialize($data);
		$time = time();
		$time_affter_5m = $data['time'] + (5*60);
		if ($time > $time_affter_5m) return response(false);
		
		$type = 'store';
		$owner_id = $data['owner_id'];
		$creator_id = $data['creator_id'];
	} else {
		switch ($params['type']) {
			case 'user':
				$type = 'user';
				$owner_id = $loggedin_user->id;
				$creator_id = $loggedin_user->id;
				break;
			case 'store':
				$type = 'store';
				$owner_id = $loggedin_user->chain_store;
				$creator_id = $loggedin_user->id;
				break
			default:
				return response(false);
				break;
		}
	}

	$cart = $cartService->checkCart($owner_id, $type, $creator_id, 0);
	if (!$cart) return response($carts);
	$carts['cart'] = $cart;
	$cart_items = $cartService->getCartItems($cart->id);
	if (!$cart_items) return response($carts);
	$total = $tax = $quantity = 0;
	foreach ($cart_items as $key => $cart_item) {
		$product = $productService->getProductByType($cart_item->product_id, 'id');
		$store = $storeService->getStoreByType($cart_item->store_id, 'id');
		$product->store = $store;
		$product->display_quantity = $cart_item->quantity;
		$product->redeem_quantity = $cart_item->redeem_quantity;
		$quantity += $cart_item->quantity;
		$total += $product->display_price*$product->display_quantity;
		$tax += $product->tax;
		$product->max_redemm_quantity = 0;
		if ($params['code']) {
			$max_redemm_quantity = $itemService->getQuantityOfItemBySnapshot($product->snapshot_id, $loggedin_user->id, 'user');
			$product->max_redemm_quantity = $max_redemm_quantity;
		}
		$carts['items'][] = $product;
	}
	$carts['quantity'] = $quantity;
	$carts['tax'] = $tax;
	$carts['total'] = $total;
	return response($carts);
});

$app->post($container['prefix'].'/cart', function (Request $request, Response $response, array $args) {
	$services = Services::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('owner_id', $params))  		$params['owner_id'] = false;
	$data = [];
	$data['type'] = "store";
	$data['owner_id'] = $params['owner_id'];
	$data['creator_id'] = $loggedin_user->id;
	$data['time'] = time();
	$encrypt = $services->encrypt(serialize($data));
	$code = $services->b64encode($encrypt);
	return response($code);
});

$app->patch($container['prefix'].'/cart', function (Request $request, Response $response, array $args) {
	$cartService = CartService::getInstance();
	$productStoreService = ProductStoreService::getInstance();
	$productService = ProductService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('cart_id', $params))  		$params['cart_id'] = false;
	if (!array_key_exists('product_id', $params))  		$params['product_id'] = false;
	if (!array_key_exists('snapshot_id', $params))  	$params['snapshot_id'] = false;
	if (!array_key_exists('store_id', $params))  		$params['store_id'] = false;
	if (!array_key_exists('quantity', $params))  		$params['quantity'] = 0;
	if (!array_key_exists('redeem_quantity', $params))  $params['redeem_quantity'] = 0;

	$cart_item = new CartItem();
	$cart_item->data->owner_id = $params['cart_id'];
	$cart_item->data->product_id = $params['product_id'];

	$cart_id = $params['cart_id'];
	$product_id = $params['product_id'];
	$store_id = $params['store_id'];
	$quantity = $params['quantity'];
	$redeem_quantity = $params['redeem_quantity'];

	$store_quantity = $productStoreService->checkQuantityInStore($product_id, $store_id);
	if (!$store_quantity) return response(false);
	if ($store_quantity->quantity < $quantity) {
	 	return response([
			'status' => false,
			'quantity' => $store_quantity->quantity
		]);
	}
	$cart_item = new CartItem();
	$cart_item->data->quantity = $quantity;
	$cart_item->data->redeem_quantity = $redeem_quantity;

	$cart_item->where = "owner_id = '{$cart_id}' AND product_id = '{$product_id}' AND store_id = '{$store_id}'";
	return response($cart_item->update());

});

$app->put($container['prefix'].'/cart', function (Request $request, Response $response, array $args) {
	$cartService = CartService::getInstance();
	$productStoreService = ProductStoreService::getInstance();
	$productService = ProductService::getInstance();
	$loggedin_user = loggedin_user();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('type', $params))  			$params['type'] = 'user';
	if (!array_key_exists('product_id', $params))  		$params['product_id'] = false;
	if (!array_key_exists('snapshot_id', $params))  	$params['snapshot_id'] = false;
	if (!array_key_exists('store_id', $params))  		$params['store_id'] = false;
	if (!array_key_exists('quantity', $params))  		$params['quantity'] = 0;
	if (!array_key_exists('redeem_quantity', $params))  $params['redeem_quantity'] = 0;

	if (!$params['product_id'] || !$params['store_id']) return response(false);

	$product_id = $params['product_id'];
	$snapshot_id = $params['snapshot_id'];
	$store_id = $params['store_id'] ;
	$quantity = $params['quantity'];
	$redeem_quantity = $params['redeem_quantity'];
	$owner_id = $loggedin_user->id;
	$type = 'user';
	if ($params['type'] == 'shop') {
		if ($loggedin_user->chain_store != $store_id) return response(false);
		$type = 'store';
		$owner_id = $store_id;
	}
	$store_quantity = $productStoreService->checkQuantityInStore($product_id, $store_id);
	if (!$store_quantity) return response(false);
	if ($store_quantity->quantity < $quantity) {
	 	return response([
			'status' => false
		]);
	}
	$cart = $cartService->checkCart($owner_id, $type, $loggedin_user->id, 0);
	$cart_id = false;
	if ($cart) {
		$cart_id = $cart->id;
	} else {
		$cart = new Cart();
		$cart->data->owner_id = $owner_id;
		$cart->data->type = $type;
		$cart->data->creator_id = $loggedin_user->id;
		$cart->data->status = 0;
		$cart_id = $cart->insert(true);
	}

	$cart_item_exist = $cartService->checkItemInCart($product_id, $store_id, $cart_id);
	if ($cart_item_exist) {
		$cart_item = new CartItem();
		$cart_item->id = $cart_item_exist->id;
		$cart_item->data->quantity = $cart_item_exist->quantity + $quantity;
		$cart_item->where = "id = '{$cart_item_exist->id}'";
		$cart_item->update();
	} else {
		$cart_item = new CartItem();
		$cart_item->data->owner_id = $cart_id;
		$cart_item->data->type = 'cart';
		$cart_item->data->product_id = $product_id;
		$cart_item->data->snapshot_id = $snapshot_id;
		$cart_item->data->store_id = $store_id;
		$cart_item->data->quantity = $quantity;
		$cart_item->data->redeem_quantity = $redeem_quantity;
		$cart_item->insert();
	}


	return response(true);

});

$app->delete($container['prefix'].'/cart', function (Request $request, Response $response, array $args) {
	$cartService = CartService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('type', $params))  $params['type'] = 'user';
	if (!array_key_exists('product_id', $params))  $params['product_id'] = false;

	$type = 'user';
	$owner_id = $loggedin_user->id;
	if ($params['type'] == 'shop') {
		$type = 'store';
		$owner_id = $loggedin_user->chain_store;
	}

	$cart = $cartService->checkCart($owner_id, $type, $loggedin_user->id, 0);
	if (!$cart) return response(false);


	$type = 'user';
	$owner_id = $loggedin_user->id;

	$cart_item = new CartItem();
	$where = "owner_id = {$cart->id}";
	if ($params['type'] == 'shop') {
		$where .= " AND store_id = {$loggedin_user->chain_store}";
	}
	if ($params['product_id']) {
		$where .= " AND product_id = {$params['product_id']}";
	}
	$cart_item->where = $where;
	return response($cart_item->delete());
});

