<?php

namespace NwLaravel\FileStorage;

use \Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Contracts\Filesystem\Filesystem as Storage;
use Intervention\Image\Image;

/**
 * Class StorageManager
 */
class StorageManager
{
    /**
     * @var Storage
     */
    protected $storage;

    /**
     * @var ImagineFactory
     */
    protected $imagineFactory;

    /**
     * Construct
     *
     * @param Storage        $storage
     * @param ImagineFactory $imagineFactory
     */
    public function __construct(Storage $storage, ImagineFactory $imagineFactory = null)
    {
        $this->storage = $storage;
        $this->imagineFactory = $imagineFactory;
    }

    /**
     * File Exists
     *
     * @param string $filename Path File
     *
     * @return bool
     */
    public function exists($filename)
    {
        try {
            return $this->storage->exists($filename);

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get Size
     *
     * @param string $filename Path File
     *
     * @return bool
     */
    public function size($filename)
    {
        try {
            return intval($this->storage->size($filename));

        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get MimeType File
     *
     * @param string $filename Path File
     *
     * @return bool
     */
    public function mimeType($filename)
    {
        try {
            return $this->storage->mimeType($filename);

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Path is Directory
     *
     * @param string $path Path Directory
     *
     * @return bool
     */
    public function isDir($path)
    {
        $mimeType = $this->mimeType($path);

        if ($this->exists($path) && (!$mimeType || $mimeType == 'directory')) {
            return true;
        }

        return false;
    }

    /**
     * Is File
     *
     * @param string $filename Path File
     *
     * @return bool
     */
    public function isFile($filename)
    {
        return !$this->isDir($filename);
    }

    /**
     * Get Meta Data
     *
     * @param string $filename Path File
     *
     * @return bool
     */
    public function metaData($filename)
    {
        try {
            return $this->storage->getMetadata($filename);

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Read Content File
     *
     * @param string $filename Path File
     *
     * @return bool
     */
    public function readFile($filename)
    {
        return $this->storage->get($filename);
    }

    /**
     * Delete File
     *
     * @param string $filename Path File
     *
     * @return bool
     */
    public function deleteFile($filename)
    {
        if ($this->isDir($filename)) {
            return false;
        }

        return $this->storage->delete($filename);
    }

    /**
     * Delete Folder
     *
     * @param string $folder
     *
     * @return bool
     */
    public function deleteFolder($folder)
    {
        if ($this->isFile($folder)) {
            return false;
        }

        return $this->storage->deleteDirectory($folder);
    }

    /**
     * Files in Folder
     *
     * @param string $path
     * @param bool   $recursive
     *
     * @return array
     */
    public function files($path, $recursive = false)
    {
        if ($this->isFile($path)) {
            return null;
        }

        return $this->storage->files($path, (bool) $recursive);
    }

    /**
     * UploadFile
     *
     * @param UploadedFile $file     Uploaded File
     * @param string       $folder   String Folder
     * @param string       $name     String Name
     * @param bool         $override Boolean Over Ride
     * @param array        $config   Array Config Upload
     *
     * @return bool
     */
    public function uploadFile(UploadedFile $file, $folder = null, $name = null, $override = false, array $config = [])
    {
        $data = $this->parseFile($file, $folder, $name, $override);

        $success = (bool) $this->storage->put($data['filename'], file_get_contents($file), $config);

        if ($success) {
            return $data;
        }

        return false;
    }

    /**
     * Upload Image
     *
     * @param UploadedFile $file     Uploaded File
     * @param string       $folder   String Folder
     * @param string       $name     String Name
     * @param array        $options  Array Options
     * @param bool         $override Boolean Over Ride
     * @param array        $config  Array Config Upload
     *
     * @return bool
     */
    public function uploadImage(
        UploadedFile $file,
        $folder = null,
        $name = null,
        array $options = [],
        $override = false,
        array $config = []
    ) {
        $pathImage = $file->getPathname();
        $data = $this->parseFile($file, $folder, $name, $override);

        if ($this->imagineFactory) {
            $width = isset($options['width']) ? intval($options['width']) : 0;
            $height = isset($options['height']) ? intval($options['height']) : 0;
            $scale = isset($options['scale']) ? (bool) $options['scale'] : true;
            $opacity = isset($options['opacity']) ? (float) $options['opacity'] : null;
            $watermark = isset($options['watermark']) ? $options['watermark'] : null;
            $quality = isset($options['quality']) ? intval($options['quality']) : 85; // Quality Deufault: 85;

            $imagine = $this->imagineFactory->make($pathImage);
            $imagine->resize($width, $height, !$scale);
            $imagine->opacity($opacity);
            $imagine->watermark($watermark);
            $imagine = $imagine->save($pathImage.'.'.$data['extension'], $quality);
            $data['size'] = $imagine->filesize();
            $content = $imagine->encode();
        } else {
            $content = file_get_contents($file);
        }

        $success = $this->storage->put($data['filename'], $content, $config);

        if ($success) {
            return $data;
        }

        return false;
    }

    /**
     * Parse Filename
     *
     * @param UploadedFile $file     Uploaded File
     * @param string       $name     String Name
     * @param string       $folder   String Folder
     * @param bool         $override Boolean Over Ride
     *
     * @return bool|array
     */
    protected function parseFile($file, $folder = null, $name = null, $override = false)
    {
        $folder = trim((string) $folder, '/');
        $folder = $folder ? "{$folder}/" : "";
        $this->storage->makeDirectory($folder);

        $name = $name ?: $file->getClientOriginalName();
        $nameOriginal = str_slug(pathinfo($name, PATHINFO_FILENAME));

        if (empty($nameOriginal)) {
            $nameOriginal = str_random(10);
        }
        $extension = $file->getClientOriginalExtension();
        $size = $file->getClientSize();
        $mime = $file->getClientMimeType();

        $sufix = '';
        $count = 1;
        do {
            $name = "{$nameOriginal}{$sufix}.{$extension}";
            $filename = "{$folder}{$name}";
            $sufix = "({$count})";
            $count++;

        } while (!$override && $this->storage->exists($filename));

        return compact('filename', 'name', 'extension', 'size', 'mime');
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->storage->{$method}(...$parameters);
    }
}
