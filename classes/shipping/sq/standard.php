<?php
namespace Amely\Shipping\SQ;

class Standard extends \Object
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
