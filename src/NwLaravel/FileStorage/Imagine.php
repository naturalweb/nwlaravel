<?php

namespace NwLaravel\FileStorage;

interface Imagine
{
    /**
     * Filesize
     *
     * @return int
     */
    public function filesize();

    /**
     * Define Resize
     *
     * @param int     $width
     * @param int     $height
     * @param boolean $force
     *
     * @return Imagine
     */
    public function resize($width, $height, $force = false);

    /**
     * Opacity
     *
     * @return Imagine
     */
    public function opacity($opacity);

    /**
     * Watermark
     *
     * @param string  $path
     * @param integer $opacity
     *
     * @return Imagine
     */
    public function watermark($path, $position = 'center', $opacity = null);

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
    public function crop($width, $height, $x, $y);

    /**
     * Rotate Image
     *
     * @param integer $angle
     *
     * @return binary
     */
    public function rotate($angle);

    /**
     * Strip Profiles
     *
     * @param string $except
     *
     * @return this
     */
    public function stripProfiles();

    /**
     * Encode
     *
     * @param string  $format
     * @param integer $quality
     *
     * @return binary
     */
    public function encode($format = null, $quality = null);

    /**
     * Save
     *
     * @param string  $path
     * @param integer $quality
     *
     * @return binary
     */
    public function save($path, $quality = null);
}