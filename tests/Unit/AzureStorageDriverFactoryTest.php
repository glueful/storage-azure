<?php

declare(strict_types=1);

namespace Glueful\Extensions\StorageAzure\Tests\Unit;

use Glueful\Extensions\StorageAzure\AzureStorageDriverFactory;
use Glueful\Storage\Contracts\StorageDriverFactoryInterface;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;

final class AzureStorageDriverFactoryTest extends TestCase
{
    public function testDriverNameIsAzure(): void
    {
        $factory = new AzureStorageDriverFactory();

        self::assertSame('azure', $factory->driver());
        self::assertInstanceOf(StorageDriverFactoryInterface::class, $factory);
    }

    public function testAvailableTrueWhenAdapterAndProxyPresent(): void
    {
        self::assertTrue((new AzureStorageDriverFactory())->available([]));
    }

    public function testAvailableFalseWhenServiceClientNotLoadable(): void
    {
        $factory = new class extends AzureStorageDriverFactory {
            protected function adapterClassPresent(): bool
            {
                return true;
            }

            protected function serviceClientPresent(): bool
            {
                return false;
            }
        };

        self::assertFalse($factory->available([]));
    }

    public function testAvailableFalseWhenAdapterNotLoadable(): void
    {
        $factory = new class extends AzureStorageDriverFactory {
            protected function adapterClassPresent(): bool
            {
                return false;
            }
        };

        self::assertFalse($factory->available([]));
    }

    public function testCreateBuildsFilesystemFromConnectionString(): void
    {
        $fs = (new AzureStorageDriverFactory())->create([
            'container' => 'media',
            'connection_string' => self::devConnectionString(),
        ]);

        self::assertInstanceOf(FilesystemOperator::class, $fs);
    }

    public function testCreateThrowsWhenContainerMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new AzureStorageDriverFactory())->create(['connection_string' => 'x']);
    }

    public function testCreateThrowsWithoutConnectionStringOrAdapter(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new AzureStorageDriverFactory())->create(['container' => 'media']);
    }

    public function testCreateBuildsFilesystemFromPrebuiltAdapter(): void
    {
        $adapter = new \League\Flysystem\Local\LocalFilesystemAdapter(sys_get_temp_dir());

        $fs = (new AzureStorageDriverFactory())->create([
            'container' => 'media',
            'adapter' => $adapter,
        ]);

        self::assertInstanceOf(FilesystemOperator::class, $fs);
    }

    public function testFeaturesDeclareCloudNonAtomicNativeUrls(): void
    {
        $features = (new AzureStorageDriverFactory())->features([]);

        self::assertFalse($features['supports_atomic_move']);
        self::assertTrue($features['cloud']);
        self::assertTrue($features['supports_native_signed_urls']);
    }

    public static function devConnectionString(): string
    {
        return 'DefaultEndpointsProtocol=https;AccountName=devstoreaccount1;'
            . 'AccountKey=' . base64_encode(str_repeat('a', 64)) . ';'
            . 'BlobEndpoint=https://devstoreaccount1.blob.core.windows.net/';
    }
}
