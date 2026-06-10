<?php

declare(strict_types=1);

namespace Glueful\Extensions\StorageAzure;

use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\BlobFlysystem\AzureBlobStorageAdapter;
use Glueful\Storage\Contracts\NativeSignedUrlProviderInterface;
use Glueful\Storage\Contracts\StorageDriverFactoryInterface;
use Glueful\Storage\Contracts\StorageHealthCheckInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Config;

class AzureStorageDriverFactory implements
    StorageDriverFactoryInterface,
    NativeSignedUrlProviderInterface,
    StorageHealthCheckInterface
{
    public function driver(): string
    {
        return 'azure';
    }

    /**
     * @param array<string, mixed> $config
     */
    public function create(array $config): FilesystemOperator
    {
        if (!$this->adapterClassPresent()) {
            throw new \InvalidArgumentException(
                'Azure adapter dependencies not available. Install azure-oss/storage-blob-flysystem.'
            );
        }

        $container = (string) ($config['container'] ?? '');
        if ($container === '') {
            throw new \InvalidArgumentException("Missing required Azure config: 'container'");
        }

        if (
            isset($config['connection_string'])
            && $config['connection_string'] !== ''
        ) {
            $serviceClient = BlobServiceClient::fromConnectionString((string) $config['connection_string']);
            $containerClient = $serviceClient->getContainerClient($container);
            $adapter = new AzureBlobStorageAdapter(
                $containerClient,
                (string) ($config['prefix'] ?? ''),
                isPublicContainer: (bool) ($config['public'] ?? false)
            );

            return new Filesystem($adapter);
        }

        if (isset($config['adapter']) && $config['adapter'] instanceof FilesystemAdapter) {
            return new Filesystem($config['adapter']);
        }

        throw new \InvalidArgumentException(
            "Unable to create Azure filesystem. Provide 'connection_string' or a prebuilt 'adapter'."
        );
    }

    protected function adapterPresent(): bool
    {
        return $this->adapterClassPresent() && $this->serviceClientPresent();
    }

    protected function adapterClassPresent(): bool
    {
        return class_exists(AzureBlobStorageAdapter::class);
    }

    protected function serviceClientPresent(): bool
    {
        return class_exists(BlobServiceClient::class);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function available(array $config): bool
    {
        return $this->adapterPresent();
    }

    /**
     * @param array<string, mixed> $config
     * @return array{supports_atomic_move: bool, supports_native_signed_urls: bool, cloud: bool}
     */
    public function features(array $config): array
    {
        return [
            'supports_atomic_move' => false,
            'supports_native_signed_urls' => true,
            'cloud' => true,
        ];
    }

    /**
     * @param array<string, mixed> $diskConfig
     */
    public function temporaryUrl(string $path, int $ttl, array $diskConfig): ?string
    {
        if (!$this->adapterPresent()) {
            return null;
        }

        $container = (string) ($diskConfig['container'] ?? '');
        if ($container === '' || (string) ($diskConfig['connection_string'] ?? '') === '') {
            return null;
        }

        try {
            $seconds = $ttl > 0 ? $ttl : (int) ($diskConfig['signed_ttl'] ?? 3600);
            $prefix = (string) ($diskConfig['prefix'] ?? '');
            $blob = $prefix !== ''
                ? rtrim($prefix, '/') . '/' . ltrim($path, '/')
                : $path;

            $serviceClient = BlobServiceClient::fromConnectionString((string) $diskConfig['connection_string']);
            $adapter = new AzureBlobStorageAdapter($serviceClient->getContainerClient($container));

            return $adapter->temporaryUrl(
                $blob,
                (new \DateTimeImmutable())->modify("+{$seconds} seconds"),
                new Config()
            );
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $diskConfig
     * @return array{ok: bool, message: string, details?: array<string, mixed>}
     */
    public function check(string $disk, array $diskConfig): array
    {
        if (!$this->available($diskConfig)) {
            return [
                'ok' => false,
                'message' => "Disk '{$disk}': Azure adapter dependencies not available.",
            ];
        }

        $container = (string) ($diskConfig['container'] ?? '');
        if ($container === '') {
            return ['ok' => false, 'message' => "Disk '{$disk}': missing 'container' config."];
        }

        try {
            $fs = $this->create($diskConfig);
            foreach ($fs->listContents('', false) as $_) {
                break;
            }

            return [
                'ok' => true,
                'message' => "Disk '{$disk}': reachable.",
                'details' => ['driver' => 'azure', 'container' => $container],
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => "Disk '{$disk}': probe failed -- "
                    . $this->summarizeProviderError($e, $diskConfig),
            ];
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function summarizeProviderError(\Throwable $e, array $config = []): string
    {
        $message = trim($e->getMessage());
        if ($message === '') {
            return $e::class;
        }

        foreach ($this->sensitiveValues($config) as $secret) {
            $message = str_replace($secret, '[redacted]', $message);
        }

        $maxLength = 140;
        if (strlen($message) <= $maxLength) {
            return $message;
        }

        return substr($message, 0, $maxLength - 3) . '...';
    }

    /**
     * @param array<string, mixed> $config
     * @return list<string>
     */
    private function sensitiveValues(array $config): array
    {
        $values = [];

        foreach (['connection_string', 'account_key'] as $key) {
            if (isset($config[$key]) && is_scalar($config[$key]) && (string) $config[$key] !== '') {
                $values[] = (string) $config[$key];
            }
        }

        $connectionString = $config['connection_string'] ?? null;
        if (is_scalar($connectionString) && (string) $connectionString !== '') {
            foreach (explode(';', (string) $connectionString) as $part) {
                [$name, $value] = array_pad(explode('=', $part, 2), 2, '');
                if (
                    in_array(strtolower($name), ['accountkey', 'sharedaccesssignature'], true)
                    && $value !== ''
                ) {
                    $values[] = $value;
                }
            }
        }

        return array_values(array_unique($values));
    }
}
