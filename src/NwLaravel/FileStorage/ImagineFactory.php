<?php

namespace NwLaravel\FileStorage;

use Intervention\Image\ImageManager;
use Intervention\Image\Image;

class ImagineFactory
{
    /**
     * @var Intervention\Image\ImageManager
     */
    protected $manager;

    /**
     * Construct
     *
     * @param ImageManager $manager
     */
    public function __construct(ImageManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Factory
     *
     * @param string $path
     *
     * @return Imagine
     */
    public function make($path)
    {
        return new Imagine($path, $this->manager);
    }
}
