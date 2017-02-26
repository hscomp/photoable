<?php

namespace Hscomp\Photoable\Traits;

use Hscomp\Photoable\Models\Photo;
use Hscomp\Photoable\PhotoUploader;
use Illuminate\Http\UploadedFile;

trait Photoable
{
    private static $instance = null;

    public function savePhoto($section_photoable, UploadedFile $uploaded_file, $withThumbs = true, $removePrevious = false)
    {
        $config = [];
        if (isset($this->photoable['settings'])) {
            $config = $this->photoable['settings'];
        }

        $photoUploader = new PhotoUploader(null, $config);

        if (!isset($this->photoable['sections'][$section_photoable])) {
            throw new \Exception('Var photoable[sections]['.$section_photoable.'] in your model is not defined.');
        }

        if ($removePrevious) {
            $previousPhotos = Photo::where('related_id', '=', $this->id)
                ->where('section_name', '=', $this->photoable['sections'][$section_photoable])
                ->where('related_type', '=', get_called_class())->get()->toArray();
        }

        $photoUploader->setRelatedId($this->id)
            ->setRelatedType(get_called_class())
            ->setSectionName($this->photoable['sections'][$section_photoable])
            ->setTmpName($uploaded_file->getPathname());

        $result = $photoUploader->save($withThumbs);
	
        if (!$result) {
            $errors = $photoUploader->getErrors();
        }

        if ($result && $removePrevious) {
            foreach ($previousPhotos as $photo) {
                $this->removePhoto($photo['id']);
            }
        }

        return $result;
    }

    public function removePhoto($id)
    {
        $photoUploader = new PhotoUploader(null, (isset($this->photoable['settings']) ? isset($this->photoable['settings']) : [] ) );

        try {
            $photoUploader->load($id);
        } catch (\Exception $e) {
            return;
        }

        $photoUploader->delete();
        return;
    }

    public function removePhotosIfExist($what)
    {
        $photos = Photo::where('related_id', '=', $this->id)
                    ->where('section_name', '=', $this->photoable['sections'][$what])
                    ->where('related_type', '=', get_called_class())->get()->toArray();

        foreach ($photos as $photo) {
           $this->removePhoto($photo['id']);
        }

        return true;
    }

    public function getDefaultPhoto($what = '', $incthumb = true)
    {
        $url = '';
        $what = ( $what == '' ? get_called_class() : $what );
        $thumb = $incthumb ? '_thumb' : '';

        switch ($what)
        {
            case 'App\\Models\\Player':
                return config('app.url') . '/images/team/players/player-default-avatar.png';
                break;
            case 'App\\Models\\Tournament':
                return config('app.url') . '/images/_default/tourney-1.jpg';
                break;
        }

        return '';
    }

    public function getPhotoableErrors()
    {
        return [];
    }


}