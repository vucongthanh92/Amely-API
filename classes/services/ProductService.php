<?php

/**
* 
*/
class ProductService extends Services
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
        $this->table = "amely_products";
    }

    public function save($data)
    {
    	$product = new Product();
    	
    }

    public function getPropertyProduct($conditions)
    {
    	$product = $this->searchObject($conditions, 0, 1);
		if (!$product) return false;
		return $product;
    }

    public function getProductByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$product = $this->getProduct($conditions);
		if (!$product) return false;
		return $product;
    }

    public function getProductsByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "IN ({$input})",
			'operation' => ''
		];
		$products = $this->getProducts($conditions, 0, 999999999);
		if (!$products) return false;
		return $products;
    }

    public function getProduct($conditions)
	{
		$product = $this->searchObject($conditions, 0, 1);
		if (!$product) return false;
		$product = $this->changeStructureInfo($product);
		return $product;
	}

	public function getProducts($conditions, $offset = 0, $limit = 10)
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
        } else {
        	array_push($images, $imageService->showImage($product->id, "default", 'product', 'large'));
        }
        if ($images) {
        	$product->images = $images;
        }
        $product->display_price = $this->getPrice($product);
        $product->display_currency = "VND";
        if ($product->sale_price) {
        	$product->display_old_price = $product->price;
        }
        return $product;
	}
}