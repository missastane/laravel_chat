<?php

namespace App\Http\Services\Image;
use Config;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Facades\Image;
use Intervention\Image\ImageManager;
class ImageService extends ImageToolsService
{
    public function save($image)
    {
        // set image
        $this->setImage($image);
        // execute provider
        $this->provider();
        // save image
        // $image->getRealPath = $_FILE['image']['tmp_name'] in php.
        // save method wants 3 arguments : image_path, image quality, image format;
        $imageManager = new ImageManager(new Driver());
        $result = $imageManager->read($image->getRealPath())->save(public_path($this->getImageAddress()), null, $this->getImageFormat());
        return $result ? $this->getImageAddress() : false;
    }

    public function fitAndSave($image, $width, $height)
    {
        // set image
        $this->setImage($image);
        // execute provider
        $this->provider();
        // save image
        //  fit method do Combine cropping and resizing to format image in a smart way.
        $imageManager = new ImageManager(new Driver());
        $result = $imageManager->read($image->getRealPath())->cover($width, $height)->save(public_path($this->getImageAddress()), null, $this->getImageFormat());
        return $result ? $this->getImageAddress() : false;
    }

    // this method makes image with 3 size and save them in one folder and choose one size as a default too.
    public function createIndexAndSave($image)
    {
        // after publish from vendor, set data in config image file 
        // get data from config
        $imageSizes = Config::get('image.index-image-sizes');

        // set image
        $this->setImage($image);

        // set directory
        $this->getImageDirectory ?? $this->setImageDirectory(date('Y') . DIRECTORY_SEPARATOR . date('m') . DIRECTORY_SEPARATOR . date('d'));

        $this->setImageDirectory($this->getImageDirectory() . DIRECTORY_SEPARATOR . time());

        // set name
        $this->getImageName() ?? $this->setImageName(time());
        $imageName = $this->getImageName();

        $indexArray = [];
        foreach ($imageSizes as $sizeAlias => $imageSize) {
            // create and set this size name
            $currentImageName = $imageName . '_' . $sizeAlias;
            $this->setImageName($currentImageName);

            
            // execute provider
            $this->provider();

            // save image
            $imageManager = new ImageManager(new Driver());
            $result = $imageManager->read($image->getRealPath())->cover($imageSize['width'], $imageSize['height'])->save(public_path($this->getImageAddress()), null, $this->getImageFormat());
            if ($result) {
                $indexArray[$sizeAlias] = $this->getImageAddress();
            } else {
                return false;
            }
        }
        $images['indexArray'] = $indexArray;
        $images['directory'] = $this->getFinalImageDirectory();
        $images['currentImage'] = Config::get('image.default-current-index-image');
        return $images;
    }

    // deletes image
    public function deleteImage($imagePath)
    {
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    // deletes all sizes of image and image folder
    public function deleteIndex($image)
    {
        $directory = public_path($image['directory']);
        $this->deleteDirectoryAndFiles($directory);
    }

    public function deleteDirectoryAndFiles($directory)
    {
        if (!is_dir($directory)) {
            return false;
        }
        // find every file in this directory and delete
        $files = glob($directory . DIRECTORY_SEPARATOR . '*', GLOB_MARK);
        foreach ($files as $file) {
            // each file may be a directory so loop should be repeated
            if (is_dir($file)) {
                deleteDirectoryAndFiles($file);
            } else {
                unlink($file);
            }
        }
        // delete directory itself
        $result = rmdir($directory);
        return $result;

    }
}