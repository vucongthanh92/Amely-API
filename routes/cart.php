<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/cart', function (Request $request, Response $response, array $args) {
	$cartService = CartService::getInstance();
	$productService = ProductService::getInstance();
	$productDetailService = ProductDetailService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	$carts = [];
	if (!$params) $params = [];
	if (!array_key_exists('type', $params))  			$params['type'] = 'user';
	$type = $params['type'];
	$owner_id = $loggedin_user->id;
	if ($params['type'] == 'shop') {
		$type = 'store';
		$owner_id = $loggedin_user->chain_store;
	}
	$creator_id = $loggedin_user->id;

	$cart = $cartService->checkCart($owner_id, $type, $loggedin_user->id, 0);
	if (!$cart) return response($carts);
	$cart_items = $cartService->getCartItems($cart->id);
	if (!$cart_items) return response($carts);
	$pdetail = [];
	$total = 0;
	foreach ($cart_items as $key => $cart_item) {
		$product = $productService->getProductByType($cart_item->product_id, 'id');
		if (!in_array($product->owner_id, $pdetail)) {
			array_push($pdetail, $product->owner_id);
		}
		$product->display_quantity = $cart_item->quantity;
		$product->redeem_quantity = $cart_item->redeem_quantity;
		$price = $product->price;
		if ($product->sale_price) $price = $product->sale_price;
		$total += $price*$product->display_quantity;
		$carts['items'][] = $product;
	}
	if (!$pdetail) return response(false);
	$pdetail = implode(',', $pdetail);
	$pdetails = $productDetailService->getDetailProductsByType($pdetail, 'id');
	if (!$pdetails) return response(false);
	$tax = 0;
	foreach ($pdetails as $key => $pdetail) {
		$tax += $pdetail->tax;
	}
	$carts['tax'] = $tax;
	$carts['total'] = $total;
	return response($carts);
});

$app->post($container['prefix'].'/cart', function (Request $request, Response $response, array $args) {
	$cartService = CartService::getInstance();
	$productService = ProductDetailService::getInstance();
	$subProductDetailService = SubProductDetailService::getInstance();
	$tax = $total = 0;
	$params = $request->getParsedBody();
    	
	$cartService->clearCart();

	if (!$params) $params = [];
	if (!array_key_exists('items', $params))  	$params['items'] = false;

	if (!$params['items']) return response(false);
	$items = $params['items'];
	$products_id = $subproducts_id = [];
	foreach ($items as $key => $item) {
		if (!in_array($item['id'], $subproducts_id)) {
			array_push($subproducts_id, $item['id']);
		}
	}
	if (!$subproducts_id) return response(false);
	$subproducts_id = implode(',', $subproducts_id);
	$subproducts = $subProductDetailService->getSubProductsByType($subproducts_id, 'id');
	if (!$subproducts) return response(false);

	$cart = [];
	foreach ($subproducts as $key => $subproduct) {
		if (!in_array($subproduct->owner_id, $products_id)) {
			array_push($products_id, $subproduct->owner_id);
		}
		foreach ($items as $key => $item) {
			if ($subproduct->current_sub_snapshot != $item['snapshot']) return response(false);
			if ($subproduct->quantity < $item['quantity']) return response(false);
			if (!in_array($item['id'], $subproducts_id)) {
				array_push($subproducts_id, $item['id']);
			}
			if ($item['id'] == $subproduct->id) {
				$subproduct->display_quantity = $item['quantity'];
			}
		}

		$price = $subProductDetailService->getPrice($subproduct);
		$total += $price;
		$subproduct->redeem_quantity = $item['redeem_quantity'];
		$subproduct->store = $item['store'];
		$cartService->saveItems($subproduct);
	}

	if (!$products_id) return response(false);
	$products_id = implode(',', $products_id);
	$products = $productService->getProductsByType($products_id, 'id');
	if (!$products) return response(false);

	foreach ($products as $key => $product) {
		$tax += $product->tax;
	}
	$cartService->saveTotal($total);
	$cartService->saveTax($tax);

	return response($cartService->getCart());

});

$app->put($container['prefix'].'/cart', function (Request $request, Response $response, array $args) {
	$cartService = CartService::getInstance();
	$productDetailService = ProductDetailService::getInstance();
	$productStoreService = ProductStoreService::getInstance();
	$productService = ProductService::getInstance();
	$loggedin_user = loggedin_user();


	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('type', $params))  			$params['type'] = 'user';
	if (!array_key_exists('product_id', $params))  		$params['product_id'] = false;
	if (!array_key_exists('store_id', $params))  		$params['store_id'] = false;
	if (!array_key_exists('quantity', $params))  		$params['quantity'] = 0;
	if (!array_key_exists('redeem_quantity', $params))  $params['redeem_quantity'] = 0;

	if (!$params['product_id'] || !$params['store_id']) return response(false);

	$product_id = $params['product_id'];
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
	$store_quantity = $productStoreService->checkQuantityInStore($product_id, $store_id, $quantity);
	if (!$store_quantity) return response(false);
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

	$cart_item_exist = $cartService->checkItemInCart($product_id, $cart_id);
	if ($cart_item_exist) {
		$cart_item = new CartItem();
		$cart_item->id = $cart_item_exist->id;
		$cart_item->data->quantity = $cart_item_exist->quantity + $quantity;
		$cart_item->update();
	} else {
		$cart_item = new CartItem();
		$cart_item->data->owner_id = $cart_id;
		$cart_item->data->type = 'cart';
		$cart_item->data->product_id = $product_id;
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

