<?php

namespace App\Http\Services\File;


class FileService extends FileToolsService
{


    public function moveToPublic($file, $fileName)
    {
        // set image
        $this->setFile($file);
        // execute provider
        $this->provider($fileName);
        // save image
        // $image->getRealPath = $_FILE['image']['tmp_name'] in php.
        // save method wants 3 arguments : image_path, image quality, image format;
        $result = $file->move(public_path($this->getFinalFileDirectory()), $this->getFinalFileName());
        return $result ? $this->getFileAddress() : false;
    }


    // public function moveToStorage($file, $fileName)
    // {
    //     // set image
    //     $this->setFile($file);
    //     // execute provider
    //     $this->provider($fileName);
    //     // save image
    //     // $image->getRealPath = $_FILE['image']['tmp_name'] in php.
    //     // save method wants 3 arguments : image_path, image quality, image format;
    //     $result = $file->move(storage_path($this->getFinalFileDirectory()), $this->getFinalFileName());
    //     return $result ? $this->getFileAddress() : false;
    // }

    public function moveToStorage($file, $fileName)
    {
        $this->setFile($file);
        $this->provider($fileName);
    
        $directory = $this->getFinalFileDirectory();
    
        // تولید یک نام منحصر به‌فرد
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $uniqueName = $originalName . '_' . uniqid() . '.' . $extension;
    
        $stored = $file->storeAs($directory, $uniqueName, 'private');
    
        // اگه فایل ذخیره شد، مسیر نهایی رو به‌روزرسانی کن تا در getFileAddress استفاده بشه
        if ($stored) {
            $this->finalFileName = $uniqueName; // اگر چنین خاصیتی داری
            return $this->getFileAddress();     // خروجی نهایی رو بر اساس این برگردون
        }
    
        return false;
    }
    


    // deletes image if file is in folder : public
    // to delete file from folder : storage, should give true as second argument to deleteFile method
    public function deleteFile($filePath, $storage = false)
    {
        if ($storage) {
            unlink(storage_path($filePath));
            return true;
        }
        if (file_exists($filePath)) {
            unlink($filePath);
            return true;
        } else {
            return false;
        }
    }


}