<?php

namespace App\Http\Services\Image;
use Config;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Facades\Image;
use Intervention\Image\ImageManager;

class ImageCacheService
{
    public function cache($imagePath, $size = '')
    {
        // get image sizes from config
        $imageSizes = Config::get('image.cache-image-sizes');

        // check if the size exists in the configuration
        if (!isset($imageSizes[$size])) {
            $size = Config::get('image.default-current-cache-image'); // default size
        }

        // get width and height based on the selected size
        $width = $imageSizes[$size]['width'];
        $height = $imageSizes[$size]['height'];


        // check if the image exists
        if (file_exists($imagePath)) {
            // create image manager instance
            $manager = new ImageManager(new Driver());

            // cache the resized image
            // $img = Cache::remember('resized_image{$id}',function ($manager) use ($imagePath, $width, $height) {
            //     return $manager->read($imagePath)->cover($width, $height);
            // }, Config::get('image.image-cache-life-time'));
            $img = Cache::remember("resized_image_{$imagePath}_{$size}", Config::get('image.image-cache-life-time'), function () use ($manager, $imagePath, $width, $height) {
                return $manager->read($imagePath)->cover($width, $height);
            });

            return response($img->encode(), 200)
            ->header('Content-Type', $img->getMimeType());
        } else {
            $result = new ImageManager(new Driver());
            $img = $result->create($width, $height)->text('image not found - 404', $width / 2, $height / 2, function ($font) {
                $font->color('#333333');
                $font->align('center');
                $font->valign('center');
                $font->file(public_path('admin-assets/fonts/IRANSans/IRANSansWeb.woff'));
                // if font-family detemins you can set font-size:
                $font->size(24);
            });
            // return $img->response();
            return response($img->encode(), 404)
                ->header('Content-Type', 'image/png');
        }
    }
}