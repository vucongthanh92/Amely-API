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

    public function save($data)
    {
    	$category = new Category();
    	$category->data->owner_id = $data['owner_id'];
		$category->data->type = $data['type'];
		$category->data->title = $data['title'];
		$category->data->description = $data['description'];
		$category->data->subtype = $data['subtype'];
		$category->data->friendly_url = $data['friendly_url'];
		$category->data->sort_order = $data['sort_order'];
		$category->data->enabled = $data['enabled'];
		$category->data->parent_id = $data['parent_id'];
		$category->data->creator_id = $data['creator_id'];
		$category->data->logo = $data['logo'];
		$category_id = $category->insert(true);
		return $category_id;
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