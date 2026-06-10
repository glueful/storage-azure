# Changelog

All notable changes to the Glueful Azure Storage Driver extension will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-06-10 -- Initial Storage Provider Pack Release

### Added

- **Azure Blob Storage driver pack** for Glueful Framework 1.54.0's storage driver registry.
- `AzureStorageDriverFactory` implementing `StorageDriverFactoryInterface`, `NativeSignedUrlProviderInterface`, and `StorageHealthCheckInterface`.
- Azure Blob Storage support through `azure-oss/storage-blob-flysystem`.
- Native SAS URL generation through the Azure OSS adapter's `temporaryUrl()` support.
- Prebuilt Flysystem adapter escape hatch for advanced applications.
- Read-only health checks for `php glueful storage:test`, with connection-string, account-key, and SAS redaction in provider errors.
- Extension service provider metadata and `storage.driver_factory` tag registration.
- Install documentation including `php glueful extensions:enable storage-azure`.
- Upgrade note for apps moving from framework 1.53 core Azure storage.
- PHPUnit, PHPCS, and PHPStan level 6 project gates.

### Notes

- Requires Glueful Framework 1.54.0 or newer.
- Existing `container`, `connection_string`, `prefix`, and `signed_ttl` disk config values carry over from the old core storage configuration.
