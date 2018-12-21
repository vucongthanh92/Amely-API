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
        
        $path = DIRECTORY_SEPARATOR."{$owner_type}".DIRECTORY_SEPARATOR."{$owner_id}".DIRECTORY_SEPARATOR."{$image_type}";
        $dir = $settings['image']['path'].$path;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $exif = exif_read_data($file->file);
        if (!empty($exif['Orientation'])) {
            $imageResource = imagecreatefromjpeg($file->file); // provided that the image is jpeg. Use relevant function otherwise
            switch ($exif['Orientation']) {
                case 3:
                $image = imagerotate($imageResource, 180, 0);
                break;
                case 6:
                $image = imagerotate($imageResource, -90, 0);
                break;
                case 8:
                $image = imagerotate($imageResource, 90, 0);
                break;
                default:
                $image = $imageResource;
            } 
        }

        imagejpeg($image, $dir . DIRECTORY_SEPARATOR . $filename, 90);

        // $file->moveTo($dir . DIRECTORY_SEPARATOR . $filename);
        $sizes = $this->image_sizes();
        foreach ($sizes as $key => $size) {
            $resize = new ResizeImage($dir . DIRECTORY_SEPARATOR . $filename);
            $resize->resizeTo($size, $size, 'maxWidth');
            $path = "{$dir}".DIRECTORY_SEPARATOR."{$key}_{$filename}";
            $resize->saveImage($path);
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
    	$path = DIRECTORY_SEPARATOR."{$type}".DIRECTORY_SEPARATOR."{$id}".DIRECTORY_SEPARATOR."avatar".DIRECTORY_SEPARATOR."{$size}_{$image}";
    	if (file_exists($settings['image']['path'].$path)) return $settings['image']['url'].$path;
		return $settings['image']['avatar'];
    }

    public function showCover($id, $image, $type = 'user',$size = 'large')
    {
        global $settings;
        $path = DIRECTORY_SEPARATOR."{$type}".DIRECTORY_SEPARATOR."{$id}".DIRECTORY_SEPARATOR."cover".DIRECTORY_SEPARATOR."{$size}_{$image}";
    	if (file_exists($settings['image']['path'].$path)) return $settings['image']['url'].$path;
    	return $settings['image']['cover'];
    }

    public function showImage($id, $image, $type = 'feed',$size = 'large')
    {
        global $settings;
        $path = DIRECTORY_SEPARATOR."{$type}".DIRECTORY_SEPARATOR."{$id}".DIRECTORY_SEPARATOR."images".DIRECTORY_SEPARATOR."{$size}_{$image}";
        if (file_exists($settings['image']['path'].$path)) return $settings['image']['url'].$path;
        return $settings['image']['avatar'];
    }

}