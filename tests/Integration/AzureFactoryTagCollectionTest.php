<?php

declare(strict_types=1);

namespace Glueful\Extensions\StorageAzure\Tests\Integration;

use Glueful\Bootstrap\ApplicationContext;
use Glueful\Container\Container;
use Glueful\Container\Definition\ValueDefinition;
use Glueful\Container\Providers\StorageProvider;
use Glueful\Container\Providers\TagCollector;
use Glueful\Extensions\StorageAzure\AzureStorageDriverFactory;
use Glueful\Extensions\StorageAzure\StorageAzureServiceProvider;
use Glueful\Extensions\StorageAzure\Tests\Unit\AzureStorageDriverFactoryTest;
use Glueful\Storage\Contracts\StorageDriverRegistryInterface;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;

final class AzureFactoryTagCollectionTest extends TestCase
{
    public function testServicesDslPinsTheDriverFactoryTag(): void
    {
        $services = StorageAzureServiceProvider::services();

        self::assertSame(
            ['storage.driver_factory'],
            $services[AzureStorageDriverFactory::class]['tags']
        );
    }

    public function testComposerManifestDeclaresGluefulExtensionProvider(): void
    {
        $json = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true);

        self::assertSame('glueful-extension', $json['type'] ?? null);
        self::assertSame(
            StorageAzureServiceProvider::class,
            $json['extra']['glueful']['provider'] ?? null
        );
    }

    public function testAzureFactoryIsCollectedIntoRegistryAndResolvesDisk(): void
    {
        $base = sys_get_temp_dir() . '/glueful-pack-' . uniqid('', true);
        mkdir($base . '/config', 0777, true);

        $provider = new StorageProvider(new TagCollector(), ApplicationContext::forTesting($base));
        $defs = $provider->defs();

        $factory = new AzureStorageDriverFactory();
        $defs['storage.driver_factory'] = new ValueDefinition('storage.driver_factory', [$factory]);

        $registry = (new Container($defs))->get(StorageDriverRegistryInterface::class);

        self::assertTrue($registry->has('azure'));
        self::assertSame($factory, $registry->get('azure'));

        $fs = $registry->get('azure')->create([
            'container' => 'media',
            'connection_string' => AzureStorageDriverFactoryTest::devConnectionString(),
        ]);
        self::assertInstanceOf(FilesystemOperator::class, $fs);
    }
}
