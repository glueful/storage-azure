<?php

declare(strict_types=1);

namespace Glueful\Extensions\StorageAzure\Tests\Unit;

use Glueful\Extensions\StorageAzure\AzureStorageDriverFactory;
use Glueful\Storage\Contracts\NativeSignedUrlProviderInterface;
use PHPUnit\Framework\TestCase;

final class AzureNativeSignedUrlTest extends TestCase
{
    public function testTemporaryUrlReturnsNullWhenCredentialsMissing(): void
    {
        self::assertNull((new AzureStorageDriverFactory())->temporaryUrl('x', 600, ['container' => 'media']));
    }

    public function testImplementsNativeSignedUrlProvider(): void
    {
        self::assertInstanceOf(NativeSignedUrlProviderInterface::class, new AzureStorageDriverFactory());
    }

    public function testTemporaryUrlBuildsSasUrlFromDevCredentials(): void
    {
        $url = (new AzureStorageDriverFactory())->temporaryUrl(
            'uploads/file.jpg',
            600,
            [
                'container' => 'media',
                'connection_string' => AzureStorageDriverFactoryTest::devConnectionString(),
            ]
        );

        self::assertIsString($url);
        self::assertStringContainsString('media/uploads/file.jpg', $url);
        self::assertStringContainsString('sig=', $url);
    }
}
