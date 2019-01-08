<?php

/**
* 
*/
class CategoryService extends Services
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
        $this->table = "amely_categories";
    }

    public function save($data, $logo = false)
    {
    	$category = new Category();
    	foreach ($data as $key => $value) {
    		$category->data->$key = $value;
    	}
    	if ($logo) {
    		$filename = getFilename();
    		$category->data->logo = $filename;
    	}
    	if ($data['id']) {
    		$category->where = "id = {$data['id']}";
    		$category_id = $category->update(true);
    	} else {
			$category_id = $category->insert(true);
    	}
    	if ($category_id) {
			if ($logo) {
				$imageService = ImageService::getInstance();			
				$imageService->uploadImage($category_id, 'category', 'images', $logo, $filename);
			}
			return $category_id;
    	}
    	return false;
    }

    public function delete($category_id)
    {
    	$productService = ProductService::getInstance();
    	$category = $this->getCategoryByType($category_id, 'id');

    	$product_params = null;
    	$product_params[] = [
			'key' => "FIND_IN_SET({$category_id}, category)",
			'value' => '',
			'operation' => ''
		];

    	$products = $productService->getProducts($product_params, 0, 9999999999);
    	if ($products) {
    		foreach ($products as $key => $product) {
    			switch ($category->subtype) {
    				case 0:
    					$market_categories = explode(',', $product->market_category);
		    			if ($market_categories) {
		    				array_diff($market_categories, [$category_id]);
		    			}
		    			$market_categories = implode(',', $market_categories);
    					break;
    				case 1:
    					$voucher_categories = explode(',', $product->voucher_category);
		    			if ($voucher_categories) {
		    				array_diff($voucher_categories, [$category_id]);
		    			}
		    			$voucher_categories = implode(',', $voucher_categories);
    					break;
    				case 2:
    					$ticket_categories = explode(',', $product->ticket_category);
		    			if ($ticket_categories) {
		    				array_diff($ticket_categories, [$category_id]);
		    			}
		    			$ticket_categories = implode(',', $ticket_categories);
    					break;
    				default:
    					# code...
    					break;
    			}
    			$categories = explode(',', $product->category);
    			if ($categories) {
    				array_diff($categories, [$category_id]);
    			}
    			$categories = implode(',', $categories);


    			$product = object_cast("Product", $product);
    			$product->data->id = $product;
    			$product->data->category = $categories;
    			$product->data->market_category = $market_categories;
    			$product->data->voucher_category = $voucher_categories;
    			$product->data->ticket_category = $ticket_categories;
    			$product->update();
    		}
    		return true;
    	}
    	$category = new Category();
    	$category->where = "id = {$category_id}";
    	return $category->delete();
    }

    public function updateStatus($category_id, $status)
    {
        $category = new Category();
        $category->data->status = $status;
        $category->data->id = $category_id;
        $category->where = "id = {$category_id}";
        return $category->update(true);
    }

    public function getCategoriesByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "IN ({$input})",
			'operation' => ''
		];
		$categories = $this->getCategories($conditions, 0, 99999999);
		if (!$categories) return false;
		return $categories;
    }

    public function getCategoryByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$category = $this->getCategory($conditions);
		if (!$category) return false;
		return $category;
    }

    public function getCategory($conditions)
	{
		$category = $this->searchObject($conditions, 0, 1);
		if (!$category) return false;
		$category = $this->changeStructureInfo($category);
		return $category;
	}

	public function getCategories($conditions, $offset = 0, $limit = 10)
	{
		$categories = $this->searchObject($conditions, $offset, $limit);
		if (!$categories) return false;
		foreach ($categories as $key => $category) {
			$category = $this->changeStructureInfo($category);
			$categories[$key] = $category;
		}
		return array_values($categories);
	}

	private function changeStructureInfo($category)
	{
		$imageService = ImageService::getInstance();
		$category->logo = $imageService->showImage($category->id, $category->logo, 'category', 'medium');
		return $category;
	}
}