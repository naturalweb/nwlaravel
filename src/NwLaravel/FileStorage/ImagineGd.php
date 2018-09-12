<?php

namespace NwLaravel\FileStorage;

use Intervention\Image\ImageManager;
use Intervention\Image\Image;

class ImagineGd implements Imagine
{
    /**
     * @var ImageManager
     */
    protected $manager;

    /**
     * @var Image
     */
    protected $image;

    /**
     * Construct
     *
     * @param string       $path
     * @param ImageManager $manager
     */
    public function __construct($path, ImageManager $manager)
    {
        $this->manager = $manager;
        $this->image = $this->manager->make($path);
    }

    /**
     * Filesize
     *
     * @return int
     */
    public function filesize()
    {
        return $this->image->filesize();
    }

    /**
     * Define Resize
     *
     * @param int     $width
     * @param int     $height
     * @param boolean $force
     *
     * @return Imagine
     */
    public function resize($width, $height, $force = false)
    {
        $width = intval($width);
        $height = intval($height);
        $callback = function () {};

        if ($width > 0 || $height > 0) {
            // AutoScale - aspectRatio
            if (!$force) {
                $callback = function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                };
            }

            $width = $width?:null;
            $height = $height?:null;
            $this->image->resize($width, $height, $callback);
        }

        return $this;
    }

    /**
     * Opacity
     *
     * @return Imagine
     */
    public function opacity($opacity)
    {
        $opacity = intval($opacity);

        if ($opacity > 0 && $opacity < 100) {
            $this->image->opacity($opacity);
        }

        return $this;
    }

    /**
     * Watermark
     *
     * @param string  $path
     * @param string  $position
     * @param integer $opacity
     *
     * @return Imagine
     */
    public function watermark($path, $position = 'center', $opacity = null)
    {
        if ($this->isImage($path)) {
            $watermark = $this->manager->make($path);

            $width = $this->image->width();
            $height = $this->image->height();
            if ($watermark->width() > $width || $watermark->height() > $height) {
                $watermark->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            if (!is_null($opacity) && $opacity >= 0 && $opacity <= 100) {
                $watermark->opacity($opacity);
            }

            $this->image->insert($watermark, $position);
        }

        return $this;
    }

    /**
     * Crop
     *
     * @param integer $width
     * @param integer $height
     * @param integer $x
     * @param integer $y
     *
     * @return binary
     */
    public function crop($width, $height, $x, $y)
    {
        $this->image->crop($width, $height, $x, $y);

        return $this;
    }

    /**
     * Rotate Image
     *
     * @param integer $angle
     *
     * @return binary
     */
    public function rotate($angle)
    {
        $angle = intval($angle);

        if ($angle > -360 && $angle < 360) {
            $this->image->rotate($angle);
        }

        return $this;
    }

    /**
     * Is Image
     *
     * @param string $path
     *
     * @return boolean
     */
    protected function isImage($path)
    {
        return (bool) ($path && is_file($path) && strpos(mime_content_type($path), 'image/')===0);
    }

    /**
     * Encode
     *
     * @param string  $format
     * @param integer $quality
     *
     * @return binary
     */
    public function encode($format = null, $quality = null)
    {
        return $this->image->encode($format, $quality);
    }

    /**
     * Save
     *
     * @param string  $path
     * @param integer $quality
     *
     * @return binary
     */
    public function save($path, $quality = null)
    {
        $this->image = $this->image->save($path, $quality);

        return $this;
    }
}
