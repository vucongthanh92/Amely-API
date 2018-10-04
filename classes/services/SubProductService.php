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
		$subproduct = $this->getSubProduct($conditions, $changeStructure);
		if (!$subproduct) return false;
		return $subproduct;
    }

    public function getSubProductsByType($input, $type ='id', $changeStructure = true)
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "IN ({$input})",
			'operation' => ''
		];
		$subproducts = $this->getSubProducts($conditions, 0, 999999999);
		if (!$subproducts) return false;
		return $subproducts;
    }

    public function getSubProduct($conditions, $changeStructure = true)
	{
		$subproduct = $this->searchObject($conditions, 0, 1);
		if (!$subproduct) return false;
		if ($changeStructure) {
			$subproduct = $this->changeStructureInfo($subproduct);
		}
		return $subproduct;
	}

	public function getSubProducts($conditions, $offset = 0, $limit = 10)
	{
		$subproducts = $this->searchObject($conditions, $offset, $limit);
		if (!$subproducts) return false;
		foreach ($subproducts as $key => $subproduct) {
			$subproduct = $this->changeStructureInfo($subproduct);
			$subproducts[$key] = $subproduct;
		}
		return array_values($subproducts);
	}

	public function getPrice($subproduct)
	{
		if ($subproduct->sale_price) {
			return $subproduct->sale_price;
		}
		return $subproduct->price;
	}

	private function changeStructureInfo($subproduct)
	{
		$imageService = ImageService::getInstance();
        $subproduct->description = html_entity_decode($subproduct->description);
        $images = [];
        if ($subproduct->images) {
        	foreach (explode(",", $subproduct->images) as $key => $image) {
        		array_push($images, $imageService->showImage($subproduct->id, $image, 'product', 'large'));
        	}
        } else {
        	array_push($images, $imageService->showImage($subproduct->id, "default", 'product', 'large'));
        }
        if ($images) {
        	$subproduct->images = $images;
        }
        $subproduct->display_price = $this->getPrice($subproduct);
        $subproduct->display_currency = "VND";
        if ($subproduct->sale_price) {
        	$subproduct->display_old_price = $subproduct->price;
        }
        return $subproduct;
	}
}