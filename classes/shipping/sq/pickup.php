<?php
namespace Amely\Shipping\SQ;

class Pickup extends \Object
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function process()
	{	
    	return true;
	}

	public function checkFee()
	{
		
	}

	public function redeemDelivery()
    {
    	return true;
    }
}

