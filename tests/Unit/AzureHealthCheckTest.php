<?php

declare(strict_types=1);

namespace Glueful\Extensions\StorageAzure\Tests\Unit;

use Glueful\Extensions\StorageAzure\AzureStorageDriverFactory;
use Glueful\Storage\Contracts\StorageHealthCheckInterface;
use PHPUnit\Framework\TestCase;

final class AzureHealthCheckTest extends TestCase
{
    public function testCheckFailsCleanlyWhenContainerMissing(): void
    {
        $result = (new AzureStorageDriverFactory())->check('media', ['connection_string' => 'x']);

        self::assertFalse($result['ok']);
        self::assertStringContainsString("missing 'container'", $result['message']);
    }

    public function testCheckTruncatesProviderFailureMessage(): void
    {
        $factory = new class extends AzureStorageDriverFactory {
            public function create(array $config): \League\Flysystem\FilesystemOperator
            {
                throw new \RuntimeException(str_repeat('x', 300));
            }
        };

        $result = $factory->check('media', [
            'container' => 'media',
            'connection_string' => AzureStorageDriverFactoryTest::devConnectionString(),
        ]);

        self::assertFalse($result['ok']);
        self::assertLessThanOrEqual(180, strlen($result['message']));
        self::assertStringEndsWith('...', $result['message']);
    }

    public function testCheckRedactsConnectionStringComponentsFromProviderFailureMessage(): void
    {
        $accountKey = base64_encode(str_repeat('k', 64));
        $sas = 'sv=2024-01-01&sig=' . str_repeat('s', 48);
        $connectionString = 'DefaultEndpointsProtocol=https;AccountName=dev;'
            . "AccountKey={$accountKey};"
            . "SharedAccessSignature={$sas};"
            . 'BlobEndpoint=https://dev.blob.core.windows.net/';

        $factory = new class ($accountKey, $sas) extends AzureStorageDriverFactory {
            public function __construct(
                private readonly string $accountKey,
                private readonly string $sas,
            ) {
            }

            public function create(array $config): \League\Flysystem\FilesystemOperator
            {
                throw new \RuntimeException(
                    "Azure failed with AccountKey={$this->accountKey}; SharedAccessSignature={$this->sas}"
                );
            }
        };

        $result = $factory->check('media', [
            'container' => 'media',
            'connection_string' => $connectionString,
        ]);

        self::assertFalse($result['ok']);
        self::assertStringNotContainsString($accountKey, $result['message']);
        self::assertStringNotContainsString($sas, $result['message']);
        self::assertStringContainsString('[redacted]', $result['message']);
    }

    public function testImplementsHealthCheck(): void
    {
        self::assertInstanceOf(StorageHealthCheckInterface::class, new AzureStorageDriverFactory());
    }
}
