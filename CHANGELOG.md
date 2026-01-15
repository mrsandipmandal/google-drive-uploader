# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2026-01-15

### Added
- **GoogleDriveService Class**: Core service for handling authentication and file operations.
- **Namespace Change**: Repository and namespace refactored to `Open\GoogleDrive` (Package name: `sandipmandal/google-drive-uploader`).
- **Robust Authentication**: Implemented `callWithRetry` mechanism to automatically handle `401 Unauthorized` errors by refreshing the token and retrying the request.
- **Token Management**: Logic to securely load, refresh, and save `access_token` and `refresh_token` in `token.json`.
- **File Uploads**: `uploadFile` method supporting direct uploads to specific folders.
- **Folder Management**: `getOrCreateFolder` method to check for existing folders or create new ones, preventing duplicates.
- **Public Permissions**: Automatic handling of file permissions to make uploaded files publicly viewable (`anyone` with `reader` role).
- **Example Script**: `examples/simple_upload.php` demonstrating usage, including auto-detection of configuration files and generating HTML `<img>` tags for uploaded images.
- **Laravel Integration**: Documentation added to `README.md` for integrating the package locally into Laravel projects.
- **Composer Support**: Full `composer.json` configuration with PSR-4 autoloading (`SandipMandal\GoogleDrive\`).

### Fixed
- Addressed `7-day expiry` issue by adding documentation on setting Google Cloud Project to "In Production".
- Fixed `undefined variable $argc` warning in example script for environments where raw CLI arguments are handled differently.

### Security
- Added `.gitignore` to ensure `credentials.json`, `token.json`, and `vendor/` are not committed to version control.
