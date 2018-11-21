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
    	$category = new Category();
    	$category->where = "id = {$category_id}";
    	return $category->delete();
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