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
    	$product->data->owner_id = $data['owner_id'];
		$product->data->type = 'shop';
		$product->data->title = $data['title'];
		$product->data->description = $data['description'];
		$product->data->sku = $data['sku'];
		$product->data->price = $data['price'];
		$product->data->model = $data['model'];
		$product->data->tag = $data['tag'];
		$product->data->number_sold = $data['number_sold'];
		$product->data->tax = $data['tax'];
		$product->data->friendly_url = $data['friendly_url'];
		$product->data->weight = $data['weight'];
		$product->data->expiry_type = $data['expiry_type'];
		$product->data->currency = $data['currency'];
		$product->data->origin = $data['origin'];
		$product->data->product_order = 0;
		$product->data->duration = $data['duration'];
		$product->data->storage_duration = $data['storage_duration'];
		$product->data->is_special = $data['is_special'];
		$product->data->product_group = $data['product_group'];
		$product->data->creator_id = $data['creator_id'];
		$product->data->custom_attributes = $data['custom_attributes'];
		$product->data->featured = 0;
		$product->data->begin_day = $data['begin_day'];
		$product->data->end_day = $data['end_day'];
		$product->data->manufacturer = $data['manufacturer'];
		$product->data->sale_price = $data['sale_price'];
		$product->data->unit = $data['unit'];
		$product->data->approved = 0;
		$product->data->enabled = 0;
		$product->data->voucher_category = $data['voucher_category'];
		$product->data->ticket_category = $data['ticket_category'];
		$product->data->shop_category = $data['shop_category'];
		$product->data->market_category = $data['market_category'];
		$product->data->category = $data['category'];
		$product->data->adjourn_price = $data['adjourn_price'];
		$product->data->images = $data['images'];
		$product->data->parent_id = $data['parent_id'];
		$product_id = $product->insert(true);
		return $product_id;
    	
    }

    public function checkSKU($sku)
    {
    	$product = $this->getProductByType($sku, 'sku');
    	if (!$product) return response(false);
    	return response($product);
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