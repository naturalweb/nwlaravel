<?php

namespace Tests\StorageManager;

use Tests\TestCase;
use Mockery as m;
use NwLaravel\FileStorage\Imagine;
use NwLaravel\FileStorage\ImagineFactory;
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

    public function testFactory()
    {
        $mockManager = m::mock(ImageManager::class);

        $mockImage = m::mock(Image::class);
        $mockManager->shouldReceive('make')
            ->once()
            ->with('path/image.jpg')
            ->andReturn($mockImage);

        $factory = new ImagineFactory($mockManager);
        $imagine = $factory->make('path/image.jpg');

        $this->assertInstanceOf(Imagine::class, $imagine);
        $this->assertEquals($mockImage, $imagine->getImage());
        $this->assertAttributeEquals($mockImage, 'image', $imagine);
        $this->assertAttributeEquals($mockManager, 'manager', $imagine);
    }
}
