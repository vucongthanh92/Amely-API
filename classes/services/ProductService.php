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
    	foreach ($data as $key => $value) {
    		$product->data->$key = $value;
    	}
    	$product->data->type = 'shop';
    	$product->data->featured = 0;
		$product->data->product_order = 0;
		$product->data->approved = 0;
		$product->data->enabled = 0;

        if ($data['id']) {
            $product->where = "id = {$data['id']}";
            return $product->update(true);
        } else {
            return $product->insert(true);
        }
    }

    public function approval($product_id)
    {
    	$snapshotService = SnapshotService::getInstance();
		$product_properties = $this->getPropertyProductByType($product_id);
        if (!$product_properties) return response(false);
        $key = $snapshotService->generateSnapshotKey($product_properties);
        $snapshot = $snapshotService->checkExistKey($key);
        if ($snapshot) {
            $snapshot_id = $snapshot->id;
        } else {
            $snapshot_data = null;
            foreach ($product_properties as $property => $product_property) {
                $snapshot_data[$property] = $product_property;
            }
            unset($snapshot_data['id']);
            $snapshot_data['code'] = $key;
            $snapshot_id = $snapshotService->save($snapshot_data);
        }

        $product = new Product();
        $product->data->approved = time();
        $product->data->snapshot_id = $snapshot_id;
        $product->where = "id = {$product_id}";
        return $product->update(true);
    }

    public function updateStatus($product_id, $status)
    {
    	$product = new Product();
    	$product->data->status = $status;
    	$product->where = "id = {$product_id}";
    	return $product->update(true);
    }

    public function checkSKU($sku)
    {
    	$product = $this->getProductByType($sku, 'sku');
    	if (!$product) return response(false);
    	return response($product);
    }

    public function getPropertyProductByType($input, $type = 'id')
    {
        $conditions[] = [
            'key' => $type,
            'value' => "= '{$input}'",
            'operation' => ''
        ];
        $product = $this->searchObject($conditions, 0, 1);
        if (!$product) return false;
        return $product;
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