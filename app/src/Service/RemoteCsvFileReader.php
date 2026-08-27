<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Service;

use IServ\FilesystemBundle\FilePicker\Domain\PickedFile;
use IServ\FilesystemBundle\Remote\RemotePickedFileReader;

final class RemoteCsvFileReader implements CsvFileReaderInterface
{
    public function __construct(
        private readonly RemotePickedFileReader $fileReader,
    ) {
    }

    public function getMimeType(PickedFile $file): ?string
    {
        $mimeType = $this->fileReader->metadata($file)['mimetype'] ?? null;

        return is_string($mimeType) ? $mimeType : null;
    }

    public function open(PickedFile $file)
    {
        return $this->fileReader->stream($file);
    }
}
