<?php

/**
* 
*/
class SubProductService extends Services
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
        $this->table = "amely_sub_products";
    }

    public function getSubProductByType($input, $type ='id', $changeStructure = true)
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$product = $this->getSubProduct($conditions, $changeStructure);
		if (!$product) return false;
		return $product;
    }

    public function getSubProduct($conditions, $changeStructure = true)
	{
		$product = $this->searchObject($conditions, 0, 1);
		if (!$product) return false;
		if ($changeStructure) {
			$product = $this->changeStructureInfo($product);
		}
		return $product;
	}

	public function getSubProducts($conditions, $offset = 0, $limit = 10)
	{
		$products = $this->searchObject($conditions, $offset, $limit);
		if (!$products) return false;
		foreach ($products as $key => $product) {
			$product = $this->changeStructureInfo($product);
			$products[$key] = $product;
		}
		return array_values($products);
	}

	public function getPrice($product)
	{
		if ($product->sale_price) {
			return $product->sale_price;
		}
		return $product->price;
	}

	private function changeStructureInfo($product)
	{
		$imageService = ImageService::getInstance();
        $product->description = html_entity_decode($product->description);
        $images = [];
        if ($product->images) {
        	foreach (explode(",", $product->images) as $key => $image) {
        		array_push($images, $imageService->showImage($product->id, $image, 'product', 'large'));
        	}
        }
        if ($images) {
        	$product->images = $images;
        }
        return $product;
        // $product->display_price = $this->getPrice($product);
        // $product->display_currency = $product->currency;
        // if ($product->sale_price) {
        // 	$product->display_old_price = $product->price;
        // }
	}
}