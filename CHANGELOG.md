# Changelog

All notable changes to this project will be documented in this file.

## [1.0.1] - 2026-01-15

### Changed
- **Namespace Refactor**: Updated PHP namespace from `SandipMandal\GoogleDrive` to `Open\GoogleDrive` to be more generic.
- **Metadata Update**: Updated author email (`mr_sandip@zohomail.in`), added homepage, keywords, and support links to `composer.json` for better Packagist discoverability.

## [1.0.0] - 2026-01-15

### Added
- **GoogleDriveService Class**: Core service for handling authentication and file operations.
- **Robust Authentication**: Implemented `callWithRetry` mechanism to automatically handle `401 Unauthorized` errors by refreshing the token and retrying the request.
- **Token Management**: Logic to securely load, refresh, and save `access_token` and `refresh_token` in `token.json` (resolving 7-day expiry issues).
- **File Uploads**: `uploadFile` method supporting direct uploads to specific folders (auto-creating folders if missing).
- **Folder Management**: `getOrCreateFolder` helper to manage directory structures.
- **Public Permissions**: Automatic handling of file permissions to make uploaded files publicly viewable (`anyone` with `reader` role).
- **Example Script**: `examples/simple_upload.php` demonstrating usage, including auto-detection of configuration files and HTML image tag generation.
- **Laravel Integration**: Documentation added to `README.md` for integrating the package locally into Laravel projects.
- **Composer Support**: Full `composer.json` configuration with PSR-4 autoloading.
