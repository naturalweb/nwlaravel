<?php

namespace Tests\StorageManager;

use Tests\TestCase;
use Mockery as m;
use NwLaravel\FileStorage\Imagine;
use NwLaravel\FileStorage\ImagineFactory;
use NwLaravel\FileStorage\ImagineGd;
use Intervention\Image\ImageManager;
use Intervention\Image\Image;

class ImagineFactoryTest extends TestCase
{
    public function testConstruct()
    {
        $mockManager = m::mock(ImageManager::class);

        $factory = new ImagineFactory($mockManager);
        $this->assertAttributeEquals($mockManager, 'manager', $factory);
    }
}
