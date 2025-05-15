<?php

namespace App\Http\Services\File;

class FileToolsService
{
    protected $file;
    protected $exclusiveDirectory;
    protected $fileDirectory;
    protected $fileName;
    protected $fileFormat;
    protected $finalFileDirectory;
    protected $finalFileName;
    protected $fileSize;

    // file that is uploaded;
    public function setFile($file)
    {
        $this->file = $file;
    }

    // exlusiveDirectory means main folders that store file:public/post_category
    public function getExclusiveDirectory()
    {
        return $this->exclusiveDirectory;
    }
    public function setExclusiveDirectory($exclusiveDirectory)
    {
        $this->exclusiveDirectory = trim($exclusiveDirectory, '/\\');
    }


    // fileDirectory means exact folder that store file: /2024-02-05/

    public function getFileDirectory()
    {
        return $this->fileDirectory;
    }

    public function setFileDirectory($fileDirectory)
    {
        $this->fileDirectory = trim($fileDirectory, '/\\');
    }

    // fileName means name of file that is made to store it;

    public function getFileName()
    {
        return $this->fileName;
    }

    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    // this method returns original file name if file is uploaded
    public function setCurrentName()
    {
        return !empty($this->file) ? $this->setFileName(pathinfo($this->file->getClientOriginalName(), PATHINFO_FILENAME)) : false;
        // $this->file->getClientOriginalName() means $_FILES['file]['name].
    }

    // fileName means format of file that store;
    public function getFileFormat()
    {
        return $this->fileFormat;
    }

    public function setFileFormat($fileFormat)
    {
        $this->fileFormat = $fileFormat;
    }

    // finalfileDirectory includes exclusiveDirectory + fileDirectory;
    public function getFinalFileDirectory()
    {
        return $this->finalFileDirectory;
    }

    public function setFinalFileDirectory($finalFileDirectory)
    {
        $this->finalFileDirectory = $finalFileDirectory;
    }

    // finalFileName includes FileName + FileFormat;
    public function getFinalFileName()
    {
        return $this->finalFileName;
    }

    public function setFinalFileName($finalFileName)
    {
        $this->finalFileName = $finalFileName;
    }
    public function getFileSize()
    {
        return $this->fileSize;
    }
    public function setFileSize($file)
    {
        $this->fileSize = $file->getSize();
    }

    // checkDirectory checks if directory does not exist, makes that. with read & write permissions for all users but they can not execute file/folder;
    protected function checkDirectory($fileDirectory)
    {
        if (!file_exists($fileDirectory)) {
            mkdir($fileDirectory, 666, true);
        }
    }

    // returns total file path.
    public function getFileAddress()
    {
        return $this->finalFileDirectory . DIRECTORY_SEPARATOR . $this->finalFileName;
    }

    protected function provider($fileName = null)
    {
        // set properties
        // if each property does not exists, we have to set it;
        // this method
        $this->getFileDirectory() ?? $this->setFileDirectory(date('Y') . DIRECTORY_SEPARATOR . date('m') . DIRECTORY_SEPARATOR . date('d'));
        $fileName = pathinfo($fileName,PATHINFO_FILENAME);
        $this->getFileName() ?? $this->setFileName($fileName? $fileName: time());
        $this->setFileFormat(pathinfo($this->file->getClientOriginalName(), PATHINFO_EXTENSION));
        
        // set final File directory
        $finalFileDirectory = empty($this->getExclusiveDirectory()) ? $this->getFileDirectory() : $this->getExclusiveDirectory() . DIRECTORY_SEPARATOR . $this->getFileDirectory();
        $this->setFinalFileDirectory($finalFileDirectory);

        // set final File name
        $this->setFinalFileName($this->getFileName().'.'.$this->getFileFormat());

        // check and create final File directory
        $this->checkDirectory($this->getFinalFileDirectory());
    }
}