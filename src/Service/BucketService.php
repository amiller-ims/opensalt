<?php

namespace App\Service;

use League\Flysystem\Filesystem;

class BucketService
{
    private $filesystem;

    public function __construct(Filesystem $filesystem) {
        $this->filesystem = $filesystem;
    }

    public function uploadFile (string $file, string $dir) {
        $filesystem = $this->filesystem;
        $path = "/$dir/".$file->getClientOriginalName().rand();

        $stream = fopen($file->getRealPath(), 'r+');
        $filesystem->writeStream($path, $stream, ['visibility' => 'public']);
        fclose($stream);

        return $path;
    }
}
