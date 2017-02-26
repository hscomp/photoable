<?php

namespace Hscomp\Photoable;

use App\Models\Photo as DbPhoto;
use App\Traits\AjaxResponse;
use App\Traits\Fileable;
use DB;
use Image;

class PhotoUploader
{
    use Fileable, AjaxResponse;

    const PHOTO_HAS_NOT_MINIMAL_DIMENSIONS = 1;

    const PHOTO_HAS_LARGE_DIMENSIONS = 1;

    /**
     * Error list.
     *
     * @var array
     */
    private $errors = [];

    /**
     * Db object.
     *
     * @var DbPhoto
     */
    private $dbPhoto;
    
    /**
     * Section id.
     *
     * @var
     */
    private $related_id;
    
    /**
     * Section type. Name of related Eloquent model.
     *
     * @var
     */
    private $relatedType;
    
    /**
     * Section name. Name of photos section folder.
     *
     * @var
     */
    private $sectionName;
    
    /**
     * Files extension.
     *
     * @var
     */
    private $filesExtension;
    
    /**
     * Compression ratio used for storing images.
     *
     * @var int
     */
    private $compression;
    
    /**
     * Photo temp name stored in global FILES variable.
     *
     * @var
     */
    private $tmpName;

    /**
     * Main file min width.
     *
     * @var
     */
    private $mainFileMinWidth;

    /**
     * Main file min height.
     *
     * @var
     */
    private $mainFileMinHeight;

    /**
     * Main file max width.
     *
     * @var
     */
    private $mainFileMaxWidth;
    
    /**
     * Main file max height.
     *
     * @var
     */
    private $mainFileMaxHeight;
    
    /**
     * Thumb file width.
     *
     * @var
     */
    private $thumbFileWidth;
    
    /**
     * Thumb file height.
     *
     * @var
     */
    private $thumbFileHeight;

    /**
     * Bigger thumb file width.
     *
     * @var
     */
    private $thumbFileBiggerWidth;

    /**
     * Bigger thumb file height.
     *
     * @var
     */
    private $thumbFileBiggerHeight;

    /**
     * Main file.
     *
     * @var Image
     */
    private $mainFile;
    
    /**
     * Thumb file.
     *
     * @var Image
     */
    private $thumbFile;
    
    /**
     * No cat thumb file.
     *
     * @var Image
     */
    private $noCatThumbFile;
    
    /**
     * Unique photo filename stored in database and stored on disk.
     *
     * @var
     */
    private $filename;

    /**
     * Public folder path.
     *
     * @var
     */
    private $publicFolderPath;

    /**
     * Base folder path.
     *
     * @var
     */
    private $rootFolderPath;
    
    /**
     * Files folder path.
     *
     * @var
     */
    private $filesFolderPath;
    
    /**
     * Main file path.
     *
     * @var
     */
    private $mainFilePath;
    
    /**
     * Thumb file path.
     *
     * @var
     */
    private $thumbFilePath;
    
    /**
     * No cut thumb file path.
     *
     * @var
     */
    private $noCutThumbFilePath;

    /**
     * Config.
     *
     * @var array
     */
    protected $config;
    
    /**
     * Photo constructor.
     *
     * @param null $id
     *
     * @internal param array $options
     */
    public function __construct($id = null, $config = [])
    {
        $this->config = $config;

        $this->compression = isset($config['compression']) ? $config['compression'] : config('photouploader.compression');

        $this->filesExtension = isset($config['defaultExtension']) ? $config['defaultExtension'] : config('photouploader.defaultExtension');

        $this->mainFileMaxWidth = isset($config['mainFileMaxWidth']) ? $config['mainFileMaxWidth'] : config('photouploader.mainFileMaxWidth');

        $this->mainFileMaxHeight = isset($config['mainFileMaxHeight']) ? $config['mainFileMaxHeight'] : config('photouploader.mainFileMaxHeight');

        $this->thumbFileWidth = isset($config['thumbFileWidth']) ? $config['thumbFileWidth'] : config('photouploader.thumbFileWidth');

        $this->thumbFileHeight = isset($config['thumbFileHeight']) ? $config['thumbFileHeight'] : config('photouploader.thumbFileHeight');

        if (! is_null($id))
        {
            $this->load($id);
        }
    }
    
    /**
     * Load photo and set base data.
     *
     * @param $id
     */
    public function load($id)
    {
        $this->dbPhoto = DbPhoto::findOrFail($id);
        
        $this->setRelatedId($this->dbPhoto->related_id)
            ->setRelatedType($this->dbPhoto->related_type)
            ->setSectionName($this->dbPhoto->section_name)
            ->setFilename($this->dbPhoto->filename)
            ->setFilesExtension($this->dbPhoto->extension)
            ->setFilesFolderPath()
            ->setFilePaths();
    }
    
    /**
     * Set file paths.
     */
    private function setFilePaths()
    {
        $thumbPrefixName = isset($this->config['thumb_prefix_name'])
            ? $this->config['thumb_prefix_name'] . '_'
            : 'thumb_';

        $this->mainFilePath = $this->filesFolderPath . '/' . $this->filename;
        
        $this->thumbFilePath = $this->filesFolderPath . '/' . $thumbPrefixName . $this->filename;
        
        $this->noCutThumbFilePath = $this->filesFolderPath . '/noCatThumb_' . $this->filename;
        
        return $this;
    }
    
    /**
     * Set file paths.
     */
    private function setFilesFolderPath()
    {
        $this->rootFolderPath = config('photouploader.storagePath');

        $this->baseFolderPath = $this->rootFolderPath . $this->sectionName;
        
        $this->filesFolderPath = $this->baseFolderPath . '/' . $this->related_id;
        
        return $this;
    }
    
    /**
     * Set final file extension.
     *
     * @param string $filesExtension
     *
     * @return $this
     */
    public function setFilesExtension($filesExtension = 'jpg')
    {
        if (in_array($filesExtension, ['jpg', 'jpeg', 'png', 'git', 'tif', 'bmp']))
        {
            $this->filesExtension = $filesExtension;
        }
        
        return $this;
    }
    
    /**
     * Set unique filename (create new filename or set the existing name from database when loading).
     *
     * @param null $filename
     *
     * @return $this
     */
    private function setFilename($filename = null)
    {
        $this->filename = ! is_null($filename)
            ? $filename
            : str_random() . time() . '.' . $this->filesExtension;
        
        return $this;
    }
    
    /**
     * Set photo section name (name of photos section folder).
     *
     * @param $sectionName
     *
     * @return $this
     */
    public function setSectionName($sectionName)
    {
        $this->sectionName = $sectionName;
        
        return $this;
    }
    
    /**
     * Set photo related type.
     *
     * @param $relatedType
     *
     * @return $this
     */
    public function setRelatedType($relatedType)
    {
        $this->relatedType = $relatedType;
        
        return $this;
    }
    
    /**
     * Set photo related id.
     *
     * @param $related_id
     *
     * @return $this
     */
    public function setRelatedId($related_id)
    {
        $this->related_id = (int)$related_id;
        
        return $this;
    }
    
    /**
     * Set image compression.
     *
     * @param int $compression
     *
     * @return $this
     */
    public function setCompression($compression = 80)
    {
        $compression = (int)$compression;
        
        if ($compression > 0 && $compression <= 100)
        {
            $this->compression = $compression;
        }
        
        return $this;
    }
    
    /**
     * Set photo tmp name.
     *
     * @param $tmpName
     *
     * @return $this
     */
    public function setTmpName($tmpName)
    {
        $this->tmpName = $tmpName;
        
        return $this;
    }
    
    /**
     * Set main file max width.
     *
     * @param $maxWidth
     */
    public function setMainFileMaxWidth($maxWidth)
    {
        $this->mainFileMaxWidth = (int)$maxWidth;
    }
    
    /**
     * Set main file max height.
     *
     * @param $maxHeight
     */
    public function setMainFileMaxHeight($maxHeight)
    {
        $this->mainFileMaxHeight = (int)$maxHeight;
    }
    
    /**
     * Set thumb file width.
     *
     * @param $width
     */
    public function setThumbFileWidth($width)
    {
        $this->thumbFileWidth = (int)$width;
    }
    
    /**
     * Set thumb file height.
     *
     * @param $height
     */
    public function setThumbFileHeight($height)
    {
        $this->thumbFileHeight = (int)$height;
    }
    
    /**
     * Get photo db object.
     *
     * @return DbPhoto
     */
    public function getDbPhoto()
    {
        return $this->dbPhoto;
    }
    
    /**
     * Save image.
     */
    public function save($create_thumbs = true)
    {
        DB::beginTransaction();
        
        $this->setFilesFolderPath()
            ->setFilename($this->filename)
            ->setFilePaths()
            ->saveInDatabase()
            ->prepareFilesFolder();
        
        if ($status = $this->saveFiles($create_thumbs))
        {
            $this->updateInDatabase('size', $this->getFreshFilesize('mainFile'));
            
            DB::commit();
        }
        else
        {
            DB::rollback();
        }
        
        return $status;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    private function addError($errorCode)
    {
        if (! in_array($errorCode, $this->errors)) {
            $this->errors[] = $errorCode;
        }
    }
    
    /**
     * Create image files folder.
     */
    private function prepareFilesFolder()
    {
        if (! is_dir($this->rootFolderPath))
        {
            mkdir($this->rootFolderPath, 0777, true);
        }

        if (! is_dir($this->baseFolderPath))
        {
            mkdir($this->baseFolderPath, 0777, true);
        }
        
        if (! is_dir($this->filesFolderPath))
        {
            mkdir($this->filesFolderPath, 0777, true);
        }
        
        return $this;
    }
    
    /**
     * Save image in database.
     */
    private function saveInDatabase()
    {
        $photoData = [
            'related_id'   => $this->related_id,
            'related_type' => $this->relatedType,
            'section_name' => $this->sectionName,
            'filename'     => $this->filename,
            'extension'    => $this->filesExtension,
        ];
        
        if (! is_null($this->dbPhoto))
        {
            $this->dbPhoto->update($photoData);
        }
        else
        {
            $this->dbPhoto = new DbPhoto($photoData);
            $this->dbPhoto->save();
        }
        
        return $this;
    }
    
    /**
     * Save files.
     */
    private function saveFiles($create_thumbs = false)
    {
        $mainFileCreated = $this->saveMainFile();

        if ($mainFileCreated && $create_thumbs) {
            if (isset($this->config['save'])) {
                return $this->savePredefinedDimensions($this->config['save']);
            } else {
                $noCutThumbFileCreated = $this->saveNoCutThumbFile();
                $thumbFileCreated = $this->saveThumbFile();
                return ($mainFileCreated && $noCutThumbFileCreated && $thumbFileCreated);
            }
        }

        return $mainFileCreated;

    }
    
    /**
     * Save main file.
     */
    private function saveMainFile()
    {
        $image_info = getimagesize($this->tmpName);

        if (!$image_info || $image_info[0] > 4000 || $image_info[1] > 4000)
        {
            $this->addError(static::PHOTO_HAS_LARGE_DIMENSIONS);

            return false;
        }

        $image = Image::make($this->tmpName);

        if (! $this->checkMinSizes($image))
        {
            $this->addError(static::PHOTO_HAS_NOT_MINIMAL_DIMENSIONS);

            return false;
        }

        $this->setMaxSizes($image, $this->mainFileMaxWidth, $this->mainFileMaxHeight);
        
        $image->encode($this->filesExtension)
            ->save($this->mainFilePath, $this->compression);

        $this->mainFile = $image;
        
        return true;
    }
    
    /**
     * Check file min sizes.
     *
     * @param $image
     *
     * @return bool
     */
    private function checkMinSizes(&$image)
    {
        if (($image->width() < $this->thumbFileWidth) || ($image->height() < $this->thumbFileHeight))
        {
            return false;
        }
        
        return true;
    }
    
    /**
     * Set file max sizes.
     *
     * @param $image Image
     * @param $maxWidth
     * @param $maxHeight
     */
    private function setMaxSizes(&$image, $maxWidth, $maxHeight)
    {
        if ($image->width() > $maxWidth)
        {
            $this->resize($image, $maxWidth, null);
        }
        
        if ($image->height() > $maxHeight)
        {
            $this->resize($image, null, $maxHeight);
        }
    }
    
    /**
     * Resize file.
     *
     * @param $image Image
     * @param $width
     * @param $height
     * @param bool $aspectRatio
     */
    private function resize(&$image, $width, $height, $aspectRatio = true)
    {
        if ($aspectRatio)
        {
            $image->resize($width, $height, function ($constraint)
            {
                $constraint->aspectRatio();
            });
        }
        else
        {
            $image->resize($width, $height);
        }
    }
    
    /**
     * Save no cat thumb file.
     *
     * This file is created always from main file, that was created earlier.
     */
    private function saveNoCutThumbFile()
    {
        if (! $this->mainFile)
        {
            return false;
        }
        
        $image = $this->mainFile;

        $dontAcpectRatio = isset($this->config['dont_aspect_ratio']);
        
        if ($image->width() >= $image->height())
        {
            if ($dontAcpectRatio) {
                $this->resize($image, $this->thumbFileWidth, null);
            } else {
                $ratio = $this->thumbFileHeight / $image->height();

                if ($image->width() * $ratio < $this->thumbFileWidth)
                {
                    $this->resize($image, $this->thumbFileWidth, null);
                }
                else
                {
                    $this->resize($image, null, $this->thumbFileHeight);
                }
            }
        }
        else
        {
            if ($dontAcpectRatio) {
                $this->resize($image, null, $this->thumbFileHeight);
            } else {
                $ratio = $this->thumbFileWidth / $image->width();

                if ($image->height() * $ratio < $this->thumbFileHeight)
                {
                    $this->resize($image, null, $this->thumbFileHeight);
                }
                else
                {
                    $this->resize($image, $this->thumbFileWidth, null);
                }
            }
        }
        
        $image->encode($this->filesExtension)
            ->save($this->noCutThumbFilePath, $this->compression);
        
        $this->noCatThumbFile = $image;
        
        return true;
    }

    public function savePredefinedDimensions($toSave)
    {
        $dontAcpectRatio = isset($this->config['dont_aspect_ratio']);

        $image = Image::make($this->mainFilePath);

        foreach ($toSave as $key => $dimension)
        {
            list($toWidth, $toHeight) = explode("x", $dimension);

            if ($dontAcpectRatio) {
                $this->resize($image, $toWidth, $toHeight);
                $image_test = Image::canvas($toWidth, $toHeight, config('photouploader.canvasBackground'));
                $image_test->insert($image, 'center');
                $image = $image_test;
            } else {
                $image->fit($toWidth, $toHeight);
            }

            $filepath = $this->filesFolderPath . '/' . $key . '_' . $this->filename;
            $image->encode($this->filesExtension)
                  ->save($filepath, $this->compression);
        }

        return true;
    }
    
    /**
     * Save thumb file.
     *
     * This file is created always from no cut thumb file, that was created earlier.
     */
    private function saveThumbFile()
    {
        if (! $this->mainFile)
        {
            return false;
        }
        
        $image = $this->noCatThumbFile;

        $dontAcpectRatio = isset($this->config['dont_aspect_ratio']);

        if ($image->width() >= $image->height())
        {
            if ($dontAcpectRatio) {
                $this->resize($image, $this->thumbFileWidth, null);
            } else {
                $this->crop($image, $this->thumbFileWidth, $this->thumbFileHeight, (($image->width() / 2) - ($this->thumbFileWidth / 2)), 0);
            }
        }
        else
        {
            if ($dontAcpectRatio) {
                $this->resize($image, null, $this->thumbFileHeight);
            } else {
                $this->crop($image, $this->thumbFileWidth, $this->thumbFileHeight, 0, (($image->height() / 2) - ($this->thumbFileHeight / 2)));
            }
        }

        $image->encode($this->filesExtension)
            ->save($this->thumbFilePath, $this->compression);
        
        $this->thumbFile = $image;
        
        return true;
    }
    
    /**
     * Crop file.
     *
     * @param $image Image
     * @param $width
     * @param $height
     * @param int $x
     * @param int $y
     */
    private function crop(&$image, $width, $height, $x = 0, $y = 0)
    {
        $width = $width > $image->width()
            ? $image->width()
            : $width;
        
        $height = $height > $image->height()
            ? $image->height()
            : $height;
        
        $x = $x > 0
            ? $x
            : 0;
        
        $y = $y > 0
            ? $y
            : 0;
        
        $x = $x + $width > $image->width()
            ? $image->width() - $width
            : $x;
        $y = $y + $height > $image->height()
            ? $image->height() - $height
            : $y;

        $width = (int)$width;
        $height = (int)$height;
        $x = (int)$x;
        $y = (int)$y;
        
        $image->crop($width, $height, $x, $y);
    }
    
    /**
     * Update photo data in database.
     *
     * @param $param
     * @param $value
     */
    private function updateInDatabase($param, $value)
    {
        $this->dbPhoto->{$param} = $value;
        
        $this->dbPhoto->save();
    }
    
    /**
     * Get fresh file size (Intervention image caches images information).
     *
     * @param $fileType
     *
     * @return int|mixed
     */
    private function getFreshFilesize($fileType)
    {
        switch ($fileType)
        {
            case 'mainFile':
                return Image::make($this->mainFilePath)->filesize();
            case 'thumbFile':
                return Image::make($this->thumbFilePath)->filesize();
            case 'noCutThumbFile':
                return Image::make($this->noCutThumbFilePath)->filesize();
            default:
                return 0;
        }
    }
    
    /**
     * Delete photo.
     *
     * @return $this
     */
    public function delete()
    {
        DB::beginTransaction();
        
        $this->setFilesFolderPath()
            ->setFilename($this->filename)
            ->setFilePaths()
            ->removeFromDatabase()
            ->prepareFilesFolder();

        if ($this->deleteFiles())
        {
            DB::commit();

            $this->dbPhoto = null;
            $this->filename = null;
        }
        else
        {
            DB::rollback();
        }
        
        return $this;
    }
    
    /**
     * Delete photo in database.
     */
    private function removeFromDatabase()
    {
        $photo = DbPhoto::where('related_id', $this->related_id)
            ->where('related_type', $this->relatedType)
            ->where('filename', $this->filename);
        
        $photo->delete();
        
        return $this;
    }
    
    /**
     * Delete files.
     */
    private function deleteFiles()
    {
        @unlink($this->mainFilePath);
        if (isset($this->config['save'])) {
            foreach ($this->config['save'] as $prefix => $dimension) {
                $basename = basename($this->mainFilePath);
                @unlink($prefix.'_'.$basename);
            }
        } else {
            @unlink($this->thumbFilePath);
            @unlink($this->noCutThumbFilePath);
        }
        
        if ($this->isEmptyFolder($this->filesFolderPath))
        {
            @rmdir($this->filesFolderPath);
        }
        
        return true;
    }
    
    /**
     * Crop thumb file.
     *
     * @param int $leftPosition
     * @param int $topPosition
     *
     * @return bool
     */
    public function cropThumbFile($leftPosition = 0, $topPosition = 0)
    {
        $image = Image::make($this->noCutThumbFilePath);
        
        $this->crop($image, $this->thumbFileWidth, $this->thumbFileHeight, $leftPosition, $topPosition);
        
        $image->save($this->thumbFilePath, $this->compression);
        
        $this->thumbFile = $image;
        
        return true;
    }
    
    /**
     * Get main file path.
     *
     * @return mixed
     */
    public function getMainFilePath()
    {
        return $this->mainFilePath;
    }
    
}
