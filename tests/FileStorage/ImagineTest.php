<?php

namespace Tests\StorageManager;

use Tests\TestCase;
use Mockery as m;
use NwLaravel\FileStorage\Imagine;
use Intervention\Image\ImageManager;
use Intervention\Image\Image;
use Intervention\Image\Constraint;

class ImagineTest extends TestCase
{
    protected $mockManager;

    protected function setUp()
    {
        parent::setUp();
        $this->mockManager = m::mock(ImageManager::class);
    }

    private function createMockImage($path)
    {
        $mockImage = m::mock(Image::class);
        $this->mockManager->shouldReceive('make')->once()->with($path)->andReturn($mockImage);

        return $mockImage;
    }

    public function testConstruct()
    {
        $mockImage = $this->createMockImage('path/image.jpg');

        $imagine = new Imagine('path/image.jpg', $this->mockManager);
        $this->assertEquals($mockImage, $imagine->getImage());
        $this->assertAttributeEquals($mockImage, 'image', $imagine);
        $this->assertAttributeEquals($this->mockManager, 'manager', $imagine);
    }

    public function testResizeWithScale()
    {
        $test = $this;

        $mockImage = $this->createMockImage('path/image.jpg');
        $mockImage->shouldReceive('resize')
            ->once()
            ->andReturnUsing(
                function ($width, $height, $callback) use ($test) {
                    $constraint = m::mock(Constraint::class);
                    $constraint->shouldReceive('aspectRatio')->once();
                    $constraint->shouldReceive('upsize')->once();

                    $test->assertEquals(50, $width);
                    $test->assertEquals(25, $height);
                    $test->assertNull($callback($constraint));
                }
            );

        $imagine = new Imagine('path/image.jpg', $this->mockManager);
        $this->assertEquals($imagine, $imagine->resize(50, 25));
    }

    public function testResizeForceSize()
    {
        $test = $this;

        $mockImage = $this->createMockImage('path/image.jpg');
        $mockImage->shouldReceive('resize')
            ->once()
            ->andReturnUsing(
                function ($width, $height, $callback) use ($test) {
                    $constraint = m::mock(Constraint::class);
                    $constraint->shouldReceive('aspectRatio')->never();
                    $constraint->shouldReceive('upsize')->never();

                    $test->assertEquals(100, $width);
                    $test->assertEquals(200, $height);
                    $test->assertNull($callback($constraint));
                }
            );

        $imagine = new Imagine('path/image.jpg', $this->mockManager);
        $this->assertEquals($imagine, $imagine->resize(100, 200, true));
    }

    public function testResizeArgsInvalid()
    {
        $mockImage = $this->createMockImage('path/image.jpg');
        $mockImage->shouldReceive('resize')->never();

        $imagine = new Imagine('path/image.jpg', $this->mockManager);
        $this->assertEquals($imagine, $imagine->resize(0, 200));
    }

    public function testOpacity()
    {
        $mockImage = $this->createMockImage('path/image.jpg');
        $mockImage->shouldReceive('opacity')->once()->with(35);

        $imagine = new Imagine('path/image.jpg', $this->mockManager);
        $this->assertEquals($imagine, $imagine->opacity(35));
    }

    public function testOpacityInvalid()
    {
        $mockImage = $this->createMockImage('path/image.jpg');
        $mockImage->shouldReceive('opacity')->never();

        $imagine = new Imagine('path/image.jpg', $this->mockManager);
        $this->assertEquals($imagine, $imagine->opacity(101));
    }

    public function testWatermarkDefault()
    {
        $mockMark = m::mock(Image::class);
        $mockMark->shouldReceive('width')->once()->andReturn(50);
        $mockMark->shouldReceive('height')->once()->andReturn(50);

        $mockImage = $this->createMockImage('path/image.jpg');
        $mockImage->shouldReceive('width')->once()->andReturn(100);
        $mockImage->shouldReceive('height')->once()->andReturn(100);
        $mockImage->shouldReceive('insert')->once()->with($mockMark, 'center');

        $watermark = __DIR__.'/_files/image.png';
        $this->mockManager->shouldReceive('make')->once()->with($watermark)->andReturn($mockMark);

        $imagine = new Imagine('path/image.jpg', $this->mockManager);
        $this->assertEquals($imagine, $imagine->watermark($watermark));
    }

    public function testWatermarkWithOpacity()
    {
        $mockMark = m::mock(Image::class);
        $mockMark->shouldReceive('opacity')->once()->with(33);
        $mockMark->shouldReceive('width')->once()->andReturn(50);
        $mockMark->shouldReceive('height')->once()->andReturn(50);

        $mockImage = $this->createMockImage('path/image.jpg');
        $mockImage->shouldReceive('width')->once()->andReturn(100);
        $mockImage->shouldReceive('height')->once()->andReturn(100);
        $mockImage->shouldReceive('insert')->once()->with($mockMark, 'bottom-left');

        $watermark = __DIR__.'/_files/image.png';
        $this->mockManager->shouldReceive('make')->once()->with($watermark)->andReturn($mockMark);

        $imagine = new Imagine('path/image.jpg', $this->mockManager);
        $this->assertEquals($imagine, $imagine->watermark($watermark, 'bottom-left', 33));
    }

    public function testWatermarkGreaterThanOrigResize()
    {
        $test = $this;
        $app = $this->app;

        $mockMark = m::mock(Image::class);
        $mockMark->shouldReceive('width')->once()->andReturn(100);
        $mockMark->shouldReceive('height')->once()->andReturn(100);
        $mockMark->shouldReceive('resize')
            ->once()
            ->andReturnUsing(
                function ($width, $height, $callback) use ($test, $app) {
                    $constraint = m::mock(Constraint::class);
                    $constraint->shouldReceive('aspectRatio')->once();
                    $constraint->shouldReceive('upsize')->once();

                    $test->assertEquals(100, $width);
                    $test->assertEquals(60, $height);
                    $test->assertNull($callback($constraint));
                }
            );

        $mockImage = $this->createMockImage('path/image.jpg');
        $mockImage->shouldReceive('width')->once()->andReturn(100);
        $mockImage->shouldReceive('height')->once()->andReturn(60);
        $mockImage->shouldReceive('insert')->once()->with($mockMark, 'center');

        $watermark = __DIR__.'/_files/image.png';
        $this->mockManager->shouldReceive('make')->once()->with($watermark)->andReturn($mockMark);

        $imagine = new Imagine('path/image.jpg', $this->mockManager);
        $this->assertEquals($imagine, $imagine->watermark($watermark));
    }

    public function testWatermarkWithFileInvalid()
    {
        $mockImage = $this->createMockImage('path/image.jpg');
        $mockImage->shouldReceive('width')->never();
        $mockImage->shouldReceive('height')->never();
        $mockImage->shouldReceive('insert')->never();

        $watermark = 'path/invalid.png';
        $this->mockManager->shouldReceive('make')->with($watermark)->never();

        $imagine = new Imagine('path/image.jpg', $this->mockManager);
        $this->assertEquals($imagine, $imagine->watermark($watermark));
    }

    public function testEncode()
    {
        $mockImage = $this->createMockImage('path/image.jpg');
        $mockImage->shouldReceive('encode')->once()->with('jpg', 80)->andReturn('contents-binary');

        $imagine = new Imagine('path/image.jpg', $this->mockManager);
        $this->assertEquals('contents-binary', $imagine->encode('jpg', 80));
    }

    public function testSave()
    {
        $output = 'save/img.jpg';

        $mockImage = $this->createMockImage('path/image.jpg');
        $mockImage->shouldReceive('save')->once()->with($output, 75)->andReturn(true);

        $imagine = new Imagine('path/image.jpg', $this->mockManager);
        $this->assertTrue($imagine->save($output, 75));
    }
}
