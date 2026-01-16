# Changelog

All notable changes to this project will be documented in this file.

## [1.0.5] - 2026-01-16

### Fixed
- **Token Handling**: Fixed `InvalidArgumentException` when `token.json` exists but is empty or contains invalid JSON. Now it gracefully ignores corrupt tokens, allowing standard re-authentication flow.
- **Directory Creation**: Added `saveToken` helper to automatically create the directory for `token.json` if it doesn't exist during authentication or token refresh. This prevents errors when the specified storage path directory is missing.

## [1.0.4] - 2026-01-16

### Fixed
- **Filename Handling**: Updated `uploadFile` and `uploadFiles` to accept optional custom filenames. This fixes the issue where uploading temporary files (e.g. from Laravel) resulted in names like `php1234.tmp` in Google Drive.

## [1.0.3] - 2026-01-16

### Added
- **Direct Image Embedding**: Added `getEmbedLink($fileId)` method to generate ready-to-use `<img src="...">` links.
- **Bulk Uploads**: Added `uploadFiles(array $paths, $folder)` method for optimized multiple file uploads (cached folder lookup).
- **Documentation Overhaul**: Complete rewrite of `README.md` for Packagist release, removing legacy references and adding clear Laravel integration guides.
- **Examples**: Added comprehensive examples for multiple file uploads and Laravel usage.

### Fixed
- **Documentation**: Removed incorrect "Zoho Mail" references copy-pasted from generic templates.
- **Git**: Added `.gitignore` to properly exclude sensitive credentials and vendor files.

## [1.0.2] - 2026-01-16

### Changed
- MIT License: Updated for better Packagist discoverability.

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
