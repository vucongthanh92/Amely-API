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

    public function uploadImage($owner_id, $owner_type, $image_type, $file, $filename)
    {
        global $settings;
        if (!in_array($image_type, ['avatar','cover','images'])) return response(false);
        
        $path = "/{$owner_type}/{$owner_id}/{$image_type}";
        $dir = $settings['image']['path'].$path;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $file->moveTo($dir . DIRECTORY_SEPARATOR . $filename . "jpg");
        $sizes = $this->image_sizes();
        foreach ($sizes as $key => $size) {
            $resize = new ResizeImage($dir . DIRECTORY_SEPARATOR . $filename);
            $resize->resizeTo($size, $size, 'maxWidth');
            $resize->saveImage("/{$dir}/{$key}_{$filename}");
        }
        return true;
    }

    public function image_sizes() {
        return array(
            'icon' => 64,
            'small' => 128,
            'medium' => 256,
            'large' => 512
        );
    }

    public function showAvatar($id, $image, $type = 'user',$size = 'large')
    {
        global $settings;
    	$path = "/{$type}/{$id}/avatar/"."{$size}_{$image}";
    	if (file_exists($settings['image']['path'].$path)) return $settings['image']['url'].$path;
		return $settings['image']['avatar'];
    }

    public function showCover($id, $image, $type = 'user',$size = 'large')
    {
        global $settings;
    	$path = "/{$type}/{$id}/cover/"."{$size}_{$image}";
    	if (file_exists($settings['image']['path'].$path)) return $settings['image']['url'].$path;
    	return $settings['image']['cover'];
    }

    public function showImage($id, $image, $type = 'feed',$size = 'large')
    {
        global $settings;
        $path = "/{$type}/{$id}/images/"."{$size}_{$image}";
        if (file_exists($settings['image']['path'].$path)) return $settings['image']['url'].$path;
        return $settings['image']['cover'];
    }

}