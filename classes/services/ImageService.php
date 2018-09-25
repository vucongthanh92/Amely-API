<?php

class ImageService extends Services
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
    }

    public function showAvatar($id, $image, $type = 'user',$size = 'larger')
    {
    	$path = "/{$type}/{$id}/avatar/"."{$size}_{$image}";
    	if (file_exists(IMAGE_PATH.$path)) return IMAGE_URL.$path;
		return AVATAR_DEFAULT;
    }

    public function showCover($id, $image, $type = 'user',$size = 'larger')
    {
    	$path = "/{$type}/{$id}/cover/"."{$size}_{$image}";
    	if (file_exists(IMAGE_PATH.$path)) return IMAGE_URL.$path;
    	return COVER_DEFAULT;
    }

    public function showImage($id, $image, $type = 'feed',$size = 'larger')
    {
        $path = "/{$type}/{$id}/image/"."{$size}_{$image}";
        if (file_exists(IMAGE_PATH.$path)) return IMAGE_URL.$path;
        return COVER_DEFAULT;
    }

}