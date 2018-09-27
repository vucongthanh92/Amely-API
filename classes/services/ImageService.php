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

    public function image_sizes() {
        return array(
            'icon' => 64,
            'small' => 128,
            'medium' => 256,
            'large' => 512
        );
    }

    public function showAvatar($id, $image, $type = 'user',$size = 'larger')
    {
        global $settings;
    	$path = "/{$type}/{$id}/avatar/"."{$size}_{$image}";
    	if (file_exists($settings['image']['path'].$path)) return $settings['image']['url'].$path;
		return $settings['image']['avatar'];
    }

    public function showCover($id, $image, $type = 'user',$size = 'larger')
    {
        global $settings;
    	$path = "/{$type}/{$id}/cover/"."{$size}_{$image}";
    	if (file_exists($settings['image']['path'].$path)) return $settings['image']['url'].$path;
    	return $settings['image']['cover'];
    }

    public function showImage($id, $image, $type = 'feed',$size = 'larger')
    {
        global $settings;
        $path = "/{$type}/{$id}/image/"."{$size}_{$image}";
        if (file_exists($settings['image']['path'].$path)) return $settings['image']['url'].$path;
        return $settings['image']['cover'];
    }

}