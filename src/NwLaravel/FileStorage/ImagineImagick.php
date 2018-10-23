<?php

namespace NwLaravel\FileStorage;

use Imagick;
use ImagickPixel;

class ImagineImagick implements Imagine
{
    /**
     * @var Imagick
     */
    protected $image;

    /**
     * Construct
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->image = new Imagick($path);
    }

    /**
     * Execute in Canvas
     *
     * @param \Closure $callback
     *
     * @return Imagine
     */
    protected function execute($callback)
    {
        $format = strtolower($this->image->getImageFormat());

        if ($format == 'gif') {
          $this->image = $this->image->coalesceImages();
          do {
              $callback($this->image);
          } while ($this->image->nextImage());

          $this->image = $this->image->deconstructImages();
        } else {
          $callback($this->image);
        }

        return $this;
    }

    /**
     * Filesize
     *
     * @return int
     */
    public function filesize()
    {
        return $this->image->getImageLength();
    }

    /**
     * Define Resize
     *
     * @param int     $maxWidth
     * @param int     $maxHeight
     * @param boolean $force
     *
     * @return Imagine
     */
    public function resize($maxWidth, $maxHeight, $force = false)
    {
        return $this->execute(function ($image) use ($maxWidth, $maxHeight, $force) {
            $width = $maxWidth;
            $height = $maxHeight;
            $imageWidth = $image->getImageWidth();
            $imageHeight = $image->getImageHeight();

            if(($maxWidth && $imageWidth > $maxWidth) || !$maxHeight) {
                $height = floor(($imageHeight/$imageWidth)*$maxWidth);
                if (!$height) {
                    $height = $imageHeight;
                }

            } else if(($maxHeight && $imageHeight > $maxHeight) || !$maxWidth) {
                $width = floor(($imageWidth/$imageHeight)*$maxHeight);
                if (!$width) {
                    $width = $imageWidth;
                }
            }

            $image->scaleImage($width, $height, !$force);
        });
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
            $this->execute(function ($image) use ($opacity) {
                $image->setImageOpacity($opacity/100);
            });
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
            $watermark = new \Imagick($path);

            $opacity = intval($opacity);
            if ($opacity > 0 && $opacity < 100) {
                $watermark->setImageOpacity($opacity/100);
            }

            $self = $this;

            $this->execute(function ($image) use ($watermark, $position, $self) {
                $self->watermarkCanvas($image, $watermark, $position);
            });
        }

        return $this;
    }

    protected function watermarkCanvas(Imagick $image, Imagick $watermark, $position = 'center')
    {
        // how big are the images?
        $iWidth = $image->getImageWidth();
        $iHeight = $image->getImageHeight();
        $wWidth = $watermark->getImageWidth();
        $wHeight = $watermark->getImageHeight();

        if ($iHeight < $wHeight || $iWidth < $wWidth) {
          // resize the watermark
          $watermark->scaleImage($iWidth, $iHeight, true);

          // get new size
          $wWidth = $watermark->getImageWidth();
          $wHeight = $watermark->getImageHeight();
        }

        $xOffset = 0;
        $yOffset = 0;

        switch ($position) {
          case 'center':
          default:
            $x = ($iWidth - $wWidth) / 2;
            $y = ($iHeight - $wHeight) / 2;
            break;
          case 'topLeft':
            $x = $xOffset;
            $y = $yOffset;
            break;
          case 'top':
          case 'topCenter':
            $x = ($iWidth - $wWidth) / 2;
            $y = $yOffset;
            break;
          case 'topRight':
            $x = $iWidth - $wWidth - $xOffset;
            $y = $yOffset;
            break;
          case 'right':
          case 'rightCenter':
            $x = $iWidth - $wWidth - $xOffset;
            $y = ($iHeight - $wHeight) / 2;
            break;
          case 'bottomRight':
            $x = $iWidth - $wWidth - $xOffset;
            $y = $iHeight - $wHeight - $yOffset;
            break;
          case 'bottom':
          case 'bottomCenter':
            $x = ($iWidth - $wWidth) / 2;
            $y = $iHeight - $wHeight - $yOffset;
            break;
          case 'bottomLeft':
            $x = $xOffset;
            $y = $iHeight - $wHeight - $yOffset;
            break;
          case 'left':
          case 'leftCenter':
            $x = $xOffset;
            $y = ($iHeight - $wHeight) / 2;
            break;
        }

        $image->compositeImage($watermark, Imagick::COMPOSITE_OVER, $x, $y);
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
        return $this->execute(function ($image) use ($width, $height, $x, $y) {
            $image->cropImage($width, $height, $x, $y);
        });
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
            $this->execute(function ($image) use ($angle) {
                $image->rotateImage('#ffffff', $angle);
            });
        }

        return $this;
    }

    /**
     * Strip Profiles
     *
     * @param string $except
     *
     * @return this
     */
    public function stripProfiles()
    {
        $profiles = $this->image->getImageProfiles('icc', true);

        $this->image->stripImage();

        if(!empty($profiles))
            $this->image->profileImage('icc', $profiles['icc']);

        return $this;
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
        return $this->image->getImagesBlob();
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
        $quality = intval($quality);
        if ($quality > 0 && $quality <= 100) {
            $this->image->setImageCompressionQuality($quality);
        }
        $this->image->writeImage($path);

        return $this;
    }
}
