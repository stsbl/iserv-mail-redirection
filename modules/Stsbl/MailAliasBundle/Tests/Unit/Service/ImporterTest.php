<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Tests\Unit\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use IServ\Bundle\IdmDataBroker\Contract\IdmGroupFetcher;
use IServ\Bundle\IdmDataBroker\Contract\IdmUserFetcher;
use IServ\FilesystemBundle\FilePicker\Domain\PickedFile;
use PHPUnit\Framework\TestCase;
use Stsbl\MailAliasBundle\Entity\Address;
use Stsbl\MailAliasBundle\Model\Import;
use Stsbl\MailAliasBundle\Service\CsvFileReaderInterface;
use Stsbl\MailAliasBundle\Service\Importer;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ImporterTest extends TestCase
{
    public function testImportsSelectedRemoteCsvFileWithoutLeakingState(): void
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "alias,\n");
        rewind($stream);

        $reader = $this->createMock(CsvFileReaderInterface::class);
        $reader->method('getMimeType')->willReturn('text/csv');
        $reader->method('open')->willReturn($stream);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->method('findOneBy')->with(['recipient' => 'alias'])->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->with(Address::class)->willReturn($repository);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Address::class));
        $entityManager->expects(self::once())->method('flush');

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $import = (new Import())
            ->setEnable(false)
            ->setFile(new PickedFile(base64_encode('remote/import.csv')));

        $result = (new Importer(
            $entityManager,
            $validator,
            $this->createMock(IdmUserFetcher::class),
            $this->createMock(IdmGroupFetcher::class),
            $reader,
        ))->import($import);

        self::assertSame([], $result->getWarnings());
        self::assertCount(1, $result->getNewAddresses());
        self::assertSame('alias', $result->getNewAddresses()[0]->getRecipient());
        self::assertFalse($result->getNewAddresses()[0]->getEnabled());
    }
}
