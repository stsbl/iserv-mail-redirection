<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Service;

use IServ\FilesystemBundle\FilePicker\Domain\PickedFile;

interface CsvFileReaderInterface
{
    public function getMimeType(PickedFile $file): ?string;

    /** @return resource */
    public function open(PickedFile $file);
}
