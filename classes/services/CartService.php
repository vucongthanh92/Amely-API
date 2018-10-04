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

    public function saveItems($items)
    {
    	$this->clearCart();
    	foreach ($items as $key => $item) {
    		$_SESSION['cart']['items'][] = $item;
    	}
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
