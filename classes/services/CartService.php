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
		
    }

    public function checkCart($owner_id, $type, $status)
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
            'key' => 'status',
            'value' => "= '{$status}'",
            'operation' => 'AND'
        ];

        $cart = $this->getCart($conditions);
        if (!$cart) return false;
        return $cart;
    }

    public function getCart($conditions)
    {
        $cart = $this->searchObject($conditions, 0, 1);
        if (!$cart) return false;
        return $cart;
    }

    public function getCart()
    {
        return $_SESSION['cart'];
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
