<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Service;

use IServ\FilesystemBundle\FilePicker\Domain\PickedFile;
use IServ\FilesystemBundle\Remote\RemoteFilesystemRegistry;
use IServ\FilesystemBundle\Remote\RemoteHttpClientInterface;
use IServ\FilesystemBundle\Remote\RemotePickedFileReader;
use IServ\FilesystemBundle\Remote\RemoteRouter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Stsbl\IServ\MailRedirection\Service\RemoteCsvFileReader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[CoversClass(RemoteCsvFileReader::class)]
final class RemoteCsvFileReaderTest extends TestCase
{
    private string $adapterDirectory;

    protected function setUp(): void
    {
        $this->adapterDirectory = sys_get_temp_dir() . '/mailalias-adapter-' . bin2hex(random_bytes(8));
        mkdir($this->adapterDirectory);
        file_put_contents($this->adapterDirectory . '/remote.json', json_encode([
            'module' => 'files',
            'source' => 'remote',
            'location' => 'https://files.example.test',
        ], JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        unlink($this->adapterDirectory . '/remote.json');
        rmdir($this->adapterDirectory);
    }

    public function testReadsMetadataAndStreamsFromTheRemoteFilePicker(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('getContext')->willReturn(new RequestContext());
        $router->method('generate')->willReturnCallback(static fn(string $name): string => $name === 'filesystem_metadata' ? '/metadata' : '/fetch');
        $router->method('setContext');
        $http = $this->createMock(RemoteHttpClientInterface::class);
        $metadata = $this->createMock(ResponseInterface::class);
        $metadata->method('toArray')->willReturn(['mimetype' => 'text/csv']);
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, 'alias,');
        rewind($stream);
        $content = new class ($stream) implements ResponseInterface {
            public function __construct(private $stream)
            {
            }

            public function getStatusCode(): int
            {
                return 200;
            }

            public function getHeaders(bool $throw = true): array
            {
                return [];
            }

            public function getContent(bool $throw = true): string
            {
                return '';
            }

            public function toArray(bool $throw = true): array
            {
                return [];
            }

            public function cancel(): void
            {
            }

            public function getInfo(?string $type = null): mixed
            {
                return null;
            }

            public function toStream()
            {
                return $this->stream;
            }
        };
        $http->expects(self::exactly(2))->method('get')->willReturnMap([
            ['https://files.example.test/metadata', $metadata],
            ['https://files.example.test/fetch', $content],
        ]);
        $reader = new RemoteCsvFileReader(new RemotePickedFileReader(
            new RemoteFilesystemRegistry(new NullLogger(), $this->adapterDirectory . '/', $this->adapterDirectory . '/'),
            new RemoteRouter($router),
            $http,
        ));
        $file = new PickedFile(base64_encode('remote/import.csv'));

        self::assertSame('text/csv', $reader->getMimeType($file));
        self::assertSame('alias,', stream_get_contents($reader->open($file)));
    }

    public function testReturnsNullForNonStringMimeTypes(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('getContext')->willReturn(new RequestContext());
        $router->method('generate')->willReturn('/metadata');
        $router->method('setContext');
        $http = $this->createMock(RemoteHttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(['mimetype' => ['text/csv']]);
        $http->method('get')->willReturn($response);
        $reader = new RemoteCsvFileReader(new RemotePickedFileReader(
            new RemoteFilesystemRegistry(new NullLogger(), $this->adapterDirectory . '/', $this->adapterDirectory . '/'),
            new RemoteRouter($router),
            $http,
        ));

        self::assertNull($reader->getMimeType(new PickedFile(base64_encode('remote/import.csv'))));
    }
}
