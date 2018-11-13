<?php

/**
* 
*/
class CartService extends Services
{
	protected static $instance = null;

	public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance =  new self();
		}
		return self::$instance;
	}

	public function __construct() 
	{
		$this->table = "amely_cart";
    }

    public function updateStatus($cart_id, $status)
    {
        $cart = new Cart();
        $cart->data->status = $status;
        $cart->where = "id = {$cart_id}";
        return $cart->update();
    }

    public function checkCart($owner_id, $type, $creator_id, $status)
    {
        $this->table = "amely_cart";
        $conditions = null;
        $conditions[] = [
            'key' => 'owner_id',
            'value' => "= {$owner_id}",
            'operation' => ''
        ];
        $conditions[] = [
            'key' => 'type',
            'value' => "= '{$type}'",
            'operation' => 'AND'
        ];
        $conditions[] = [
            'key' => 'creator_id',
            'value' => "= '{$creator_id}'",
            'operation' => 'AND'
        ];
        $conditions[] = [
            'key' => 'status',
            'value' => "= '{$status}'",
            'operation' => 'AND'
        ];

        $cart = $this->getCart($conditions);
        if (!$cart) return false;
        return $cart;
    }

    public function getCartByType($input, $type)
    {
        $conditions = null;
        $conditions[] = [
            'key' => $type,
            'value' => "= {$input}",
            'operation' => ''
        ];
        
        $cart = $this->searchObject($conditions, 0, 1);
        if (!$cart) return false;
        return $cart;
    }

    public function getCart($conditions)
    {
        $this->table = "amely_cart";
        $cart = $this->searchObject($conditions, 0, 1);
        if (!$cart) return false;
        return $cart;
    }

    public function checkItemInCart($product_id, $store_id, $cart_id)
    {
        $conditions = null;
        $conditions[] = [
            'key' => 'type',
            'value' => "= 'cart'",
            'operation' => ''
        ];
        $conditions[] = [
            'key' => 'owner_id',
            'value' => "= {$cart_id}",
            'operation' => 'AND'
        ];
        $conditions[] = [
            'key' => 'product_id',
            'value' => "= {$product_id}",
            'operation' => 'AND'
        ];
        $conditions[] = [
            'key' => 'store_id',
            'value' => "= {$store_id}",
            'operation' => 'AND'
        ];
        
        $this->table = "amely_cart_items";
        $cart_item = $this->searchObject($conditions, 0, 1);
        if (!$cart_item) return false;
        return $cart_item;
    }

    public function getCartItems($cart_id)
    {
        $conditions = null;
        $conditions[] = [
            'key' => 'type',
            'value' => "= 'cart'",
            'operation' => ''
        ];
        $conditions[] = [
            'key' => 'owner_id',
            'value' => "= {$cart_id}",
            'operation' => 'AND'
        ];
        $this->table = "amely_cart_items";
        $cart_items = $this->searchObject($conditions, 0, 999999999);
        if (!$cart_items) return false;
        return $cart_items;
    }    


    public function saveItems($item)
    {
		$_SESSION['cart']['items'][] = $item;
    }

    public function saveTotal($total)
    {
    	$_SESSION['cart']['total'] = $total;
    }

    public function saveTax($tax)
    {
    	$_SESSION['cart']['tax'] = $tax;
    }

    public function clearCart()
    {
    	unset($_SESSION['cart']);
    }

}
