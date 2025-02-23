<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\CoreApi\Endpoint\File;

use Psr\Http\Message\StreamInterface;

interface FileInterface
{
    public function hashFile(string $filename): string;

    public function hashStream(StreamInterface $stream): string;

    public function initUpload(string $hash, int $size, string $filename, string $mimetype): int;

    public function addChunk(string $hash, string $chunk): int;

    public function uploadFile(string $realPath, string $mimeType = null): string;

    public function uploadStream(StreamInterface $stream, string $filename, string $mimeType): string;

    public function headFile(string $realPath): bool;

    public function headHash(string $hash): bool;
}
