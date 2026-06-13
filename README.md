# Glueful Storage Azure

Azure Blob Storage driver for Glueful.

## Install

```bash
composer require glueful/storage-azure
php glueful extensions:enable storage-azure
```

The package auto-registers as a Glueful extension through
`extra.glueful.provider`. After install, any disk with `driver => azure` is
resolved by `AzureStorageDriverFactory`.

## Configuration

Add a disk under `config/storage.php`:

```php
'azure' => [
    'driver' => 'azure',
    'container' => env('AZURE_STORAGE_CONTAINER'),
    'connection_string' => env('AZURE_STORAGE_CONNECTION_STRING'),
    'prefix' => env('AZURE_STORAGE_PREFIX', ''),
    'signed_ttl' => (int) env('AZURE_SIGNED_URL_TTL', 3600),
    'max_signed_ttl' => (int) env('AZURE_MAX_SIGNED_URL_TTL', 86400),
],
```

Environment variables:

```dotenv
AZURE_STORAGE_CONTAINER=media
AZURE_STORAGE_CONNECTION_STRING=DefaultEndpointsProtocol=https;AccountName=...
AZURE_STORAGE_PREFIX=
AZURE_SIGNED_URL_TTL=3600
AZURE_MAX_SIGNED_URL_TTL=86400
```

`connection_string` is used for filesystem construction and native SAS URL
generation. The connection string must include credentials that can sign SAS
URLs.

### Upgrading from framework 1.53 Azure storage

Framework 1.54 moves Azure storage into this provider pack and this package uses
`azure-oss/storage-blob-flysystem` instead of the older
`league/flysystem-azure-blob-storage` plus Microsoft SDK stack. Existing
`container`, `connection_string`, `prefix`, and `signed_ttl` disk config values
carry over unchanged.

If an application already builds its own Flysystem adapter, it may still pass a
prebuilt `adapter` instance in the disk config. The provider pack wraps that
adapter directly and does not require a connection string for that escape hatch.
This is an advanced/test seam for programmatic configuration, not the normal
production configuration path.

## Native URLs

The framework always supports app-signed blob URLs through `/blobs/{uuid}`.
Direct provider URLs are opt-in and visibility-scoped:

```php
// config/uploads.php
'native_urls' => [
    'disks' => [
        'azure' => [
            'enabled' => true,
            'public' => true,
            'private' => false,
            'private_ttl' => 300,
        ],
    ],
    'max_private_ttl' => 900,
],
```

Private native URLs are bearer tokens from Azure Blob Storage. Keep them
short-lived and prefer the app-signed URL when application-side authorization
or revocation matters.
Direct provider URL TTLs are clamped by the disk's `max_signed_ttl` value, which
defaults to 86400 seconds.

## Diagnostics

Run a read-only check:

```bash
php glueful storage:test azure
```

Run a write/read/delete smoke test only when you want to verify write
permissions:

```bash
php glueful storage:test azure --write
```

## Development

```bash
composer test
composer run analyze
composer run phpcs
```
