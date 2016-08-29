<?php

namespace Tests\StorageManager;

use Tests\TestCase;
use Mockery as m;
use NwLaravel\FileStorage\StorageManager;
use NwLaravel\FileStorage\Imagine;
use NwLaravel\FileStorage\ImagineFactory;
use Illuminate\Contracts\Filesystem\Filesystem as Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class StorageManagerTest extends TestCase
{
    public function testShouldFileExists()
    {
        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('exists')->once()->with('path/file/foobar.jpg')->andReturn(true);

        $fileStorage = new StorageManager($mockStorage);
        $this->assertTrue($fileStorage->exists('path/file/foobar.jpg'));
    }

    public function testShouldFileNotExistsWithException()
    {
        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('exists')->andThrow(new \Exception("Error Processing"));

        $fileStorage = new StorageManager($mockStorage);
        $this->assertFalse($fileStorage->exists('path/file/foobar.jpg'));
    }

    public function testShouldGetSize()
    {
        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('size')->once()->with('path/file/foobar.jpg')->andReturn(123);

        $fileStorage = new StorageManager($mockStorage);
        $this->assertSame(123, $fileStorage->size('path/file/foobar.jpg'));
    }

    public function testShouldGetSizeWithException()
    {
        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('size')->andThrow(new \Exception("Error Processing"));

        $fileStorage = new StorageManager($mockStorage);
        $this->assertSame(0, $fileStorage->size('path/file/foobar.jpg'));
    }

    public function testShouldMimeType()
    {
        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('mimeType')->once()->with('path/file/foobar.jpg')->andReturn('image/jpg');

        $fileStorage = new StorageManager($mockStorage);
        $this->assertEquals('image/jpg', $fileStorage->mimeType('path/file/foobar.jpg'));
    }

    public function testShouldMimeTypeWithException()
    {
        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('mimeType')->andThrow(new \Exception("Error Processing"));

        $fileStorage = new StorageManager($mockStorage);
        $this->assertNull($fileStorage->mimeType('path/file/foobar.jpg'));
    }

    public function testShouldIsDir()
    {
        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('exists')->once()->with('path/folder')->andReturn(true);
        $mockStorage->shouldReceive('mimeType')->once()->with('path/folder')->andReturn(false);

        $fileStorage = new StorageManager($mockStorage);
        $this->assertTrue($fileStorage->isDir('path/folder'));
    }

    public function testShouldIsDirFalse()
    {
        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('exists')->once()->with('path/folder')->andReturn(true);
        $mockStorage->shouldReceive('mimeType')->once()->with('path/folder')->andReturn('image/png');

        $fileStorage = new StorageManager($mockStorage);
        $this->assertFalse($fileStorage->isDir('path/folder'));
    }

    public function testShouldIsFile()
    {
        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('exists')->once()->with('path/folder')->andReturn(true);
        $mockStorage->shouldReceive('mimeType')->once()->with('path/folder')->andReturn('image/png');

        $fileStorage = new StorageManager($mockStorage);
        $this->assertTrue($fileStorage->isFile('path/folder'));
    }

    public function testShouldIsFileFalse()
    {
        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('exists')->once()->with('path/folder')->andReturn(true);
        $mockStorage->shouldReceive('mimeType')->once()->with('path/folder')->andReturn(false);

        $fileStorage = new StorageManager($mockStorage);
        $this->assertFalse($fileStorage->isFile('path/folder'));
    }

    public function testShouldMetaData()
    {
        $metaData = ['size' => 233, 'mime' => 'image/jpg'];
        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('getMetadata')->once()->with('path/file/foobar.jpg')->andReturn($metaData);

        $fileStorage = new StorageManager($mockStorage);
        $this->assertEquals($metaData, $fileStorage->metaData('path/file/foobar.jpg'));
    }

    public function testShouldReadFile()
    {
        $content = 'content file foobar';
        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('get')->once()->with('path/file/foobar.txt')->andReturn($content);

        $fileStorage = new StorageManager($mockStorage);
        $this->assertEquals($content, $fileStorage->readFile('path/file/foobar.txt'));
    }

    public function testShouldMetaDataWithException()
    {
        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('getMetadata')->andThrow(new \Exception("Error Processing"));

        $fileStorage = new StorageManager($mockStorage);
        $this->assertNull($fileStorage->metaData('path/file/foobar.jpg'));
    }

    public function testShouldDeleteFileSuccess()
    {
        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('exists')->once()->with('path/file/foobar.jpg')->andReturn(true);
        $mockStorage->shouldReceive('mimeType')->once()->with('path/file/foobar.jpg')->andReturn('image/jpg');
        $mockStorage->shouldReceive('delete')->once()->with('path/file/foobar.jpg')->andReturn(true);

        $fileStorage = new StorageManager($mockStorage);
        $this->assertTrue($fileStorage->deleteFile('path/file/foobar.jpg'));
    }

    public function testShouldReturnFalseDeleteFileWithDirectory()
    {
        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('exists')->once()->with('path/folder')->andReturn(true);
        $mockStorage->shouldReceive('mimeType')->once()->with('path/folder')->andReturn('directory');
        $mockStorage->shouldReceive('delete')->never();

        $fileStorage = new StorageManager($mockStorage);

        $this->assertFalse($fileStorage->deleteFile('path/folder'));
    }

    public function testUploadFile()
    {
        $pathfile = __DIR__.'/_files/file.txt';
        $folderUpload = 'path/upload';
        $nameUpload = 'file(1).txt';
        $filename = "{$folderUpload}/{$nameUpload}";
        $size = sizeof($pathfile);
        $file = new UploadedFile($pathfile, basename($pathfile), 'plan/txt', $size);
        $content = file_get_contents($pathfile);

        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('makeDirectory')->once()->ordered()->with("{$folderUpload}/")->andReturn(true);
        $mockStorage->shouldReceive('exists')->once()->ordered()->with("{$folderUpload}/file.txt")->andReturn(true);
        $mockStorage->shouldReceive('exists')->once()->ordered()->with($filename)->andReturn(false);
        $mockStorage->shouldReceive('put')->once()->ordered()->with($filename, $content)->andReturn(true);

        $fileStorage = new StorageManager($mockStorage);

        $expected = [
            'filename' => $filename,
            'name' => $nameUpload,
            'extension' => 'txt',
            'size' => $size,
            'mime' => 'plan/txt',
        ];
        $this->assertEquals($expected, $fileStorage->uploadFile($file, $folderUpload));
    }

    public function testUploadFileShouldReturnFalse()
    {
        $pathfile = __DIR__.'/_files/file.txt';
        $folderUpload = 'path/upload';
        $nameUpload = 'file.txt';
        $filename = "{$folderUpload}/{$nameUpload}";
        $file = new UploadedFile($pathfile, basename($pathfile));
        $content = file_get_contents($pathfile);

        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('makeDirectory')->once()->ordered()->with("{$folderUpload}/")->andReturn(true);
        $mockStorage->shouldReceive('exists')->once()->ordered()->with($filename)->andReturn(false);
        $mockStorage->shouldReceive('put')->once()->ordered()->with($filename, $content)->andReturn(false);

        $fileStorage = new StorageManager($mockStorage);

        $this->assertFalse($fileStorage->uploadFile($file, $folderUpload));
    }

    public function testUploadImage()
    {
        $pathfile = __DIR__.'/_files/image.png';
        $pathwater = 'watermark.png';
        $folderUpload = 'path/upload';
        $nameUpload = 'novonome.png';
        $filename = "{$folderUpload}/{$nameUpload}";
        $size = sizeof($pathfile);
        $file = new UploadedFile($pathfile, basename($pathfile), 'image/png', $size);
        $options = ['width' => 50, 'height' => 25, 'scale' => false, 'opacity' => 30, 'watermark' => $pathwater, 'quality' => 77];
        $content = 'conteudo binary image';

        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('makeDirectory')->once()->ordered()->with("{$folderUpload}/")->andReturn(true);
        $mockStorage->shouldReceive('exists')->never();
        $mockStorage->shouldReceive('put')->once()->ordered()->with($filename, $content)->andReturn(true);

        $mockImagine = m::mock(Imagine::class);
        $mockImagine->shouldReceive('resize')->once()->with(50, 25, true)->andReturn($mockImagine);
        $mockImagine->shouldReceive('opacity')->once()->with(30)->andReturn($mockImagine);
        $mockImagine->shouldReceive('watermark')->once()->with($pathwater)->andReturn($mockImagine);
        $mockImagine->shouldReceive('encode')->once()->with('png', 77)->andReturn($content);

        $mockFactory = m::mock(ImagineFactory::class);
        $mockFactory->shouldReceive('make')->once()->with($pathfile)->andReturn($mockImagine);

        $fileStorage = new StorageManager($mockStorage, $mockFactory);

        $expected = [
            'filename' => $filename,
            'name' => $nameUpload,
            'extension' => 'png',
            'size' => $size,
            'mime' => 'image/png',
        ];
        $this->assertEquals($expected, $fileStorage->uploadImage($file, $folderUpload, $nameUpload, $options, true));
    }

    public function testUploadImageWithoutImagineFactory()
    {
        $pathfile = __DIR__.'/_files/image.png';
        $folderUpload = 'path/upload';
        $nameUpload = 'novonome.png';
        $filename = "{$folderUpload}/{$nameUpload}";
        $size = sizeof($pathfile);
        $file = new UploadedFile($pathfile, basename($pathfile), 'image/png', $size);
        $options = ['width' => 50, 'height' => 25, 'scale' => false, 'opacity' => 30];
        $content = file_get_contents($pathfile);

        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('makeDirectory')->once()->ordered()->with("{$folderUpload}/")->andReturn(true);
        $mockStorage->shouldReceive('exists')->never();
        $mockStorage->shouldReceive('put')->once()->ordered()->with($filename, $content)->andReturn(true);

        $fileStorage = new StorageManager($mockStorage);

        $expected = [
            'filename' => $filename,
            'name' => $nameUpload,
            'extension' => 'png',
            'size' => $size,
            'mime' => 'image/png',
        ];
        $this->assertEquals($expected, $fileStorage->uploadImage($file, $folderUpload, $nameUpload, $options, true));
    }

    public function testUploadImageNameRandom()
    {
        $pathfile = __DIR__.'/_files/image.png';
        $folderUpload = 'path/upload';
        $nameUpload = '#.png';
        $filename = "{$folderUpload}/{$nameUpload}";
        $size = sizeof($pathfile);
        $file = new UploadedFile($pathfile, basename($pathfile), 'image/png', $size);
        $options = ['width' => 50, 'height' => 25, 'scale' => false, 'opacity' => 30];

        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('makeDirectory')->once()->ordered()->with("{$folderUpload}/")->andReturn(true);
        $mockStorage->shouldReceive('exists')->never();
        $mockStorage->shouldReceive('put')->once()->ordered()->andReturn(true);

        $fileStorage = new StorageManager($mockStorage);

        $expected = [
            'filename' => $filename,
            'name' => $nameUpload,
            'extension' => 'png',
            'size' => $size,
            'mime' => 'image/png',
        ];
        $return = $fileStorage->uploadImage($file, $folderUpload, $nameUpload, $options, true);

        $this->assertRegExp('/^[a-z0-9A-Z]{10}\.png$/', $return['name']);
        $this->assertEquals($folderUpload.'/'.$return['name'], $return['filename']);
    }

    public function testUploadImageInvalid()
    {
        $pathfile = __DIR__.'/_files/image.png';
        $folderUpload = 'path/upload';
        $nameUpload = 'novonome.png';
        $filename = "{$folderUpload}/{$nameUpload}";
        $size = sizeof($pathfile);
        $file = new UploadedFile($pathfile, basename($pathfile), 'image/png', $size);
        $content = file_get_contents($pathfile);

        $mockStorage = m::mock(Storage::class);
        $mockStorage->shouldReceive('makeDirectory')->once()->ordered()->with("{$folderUpload}/")->andReturn(true);
        $mockStorage->shouldReceive('exists')->once()->ordered()->with($filename)->andReturn(false);
        $mockStorage->shouldReceive('put')->once()->ordered()->with($filename, $content)->andReturn(false);

        $fileStorage = new StorageManager($mockStorage);

        $this->assertFalse($fileStorage->uploadImage($file, $folderUpload, $nameUpload));
    }
}
