<?php

declare(strict_types=1);

namespace Glueful\Extensions\StorageAzure;

use Glueful\Bootstrap\ApplicationContext;
use Glueful\Extensions\ServiceProvider;

final class StorageAzureServiceProvider extends ServiceProvider
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function services(): array
    {
        return [
            AzureStorageDriverFactory::class => [
                'class' => AzureStorageDriverFactory::class,
                'shared' => true,
                'tags' => ['storage.driver_factory'],
            ],
        ];
    }

    public function register(ApplicationContext $context): void
    {
        $this->mergeConfig('storage-azure', require __DIR__ . '/../config/storage-azure.php');
    }

    public function boot(ApplicationContext $context): void
    {
    }
}
