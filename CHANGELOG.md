# Changelog

All notable changes to this project will be documented in this file.

## [1.0.12]

### Changed
- Renamed package from `sandipmandal/google-drive-uploader` to `open-php/google-drive-uploader`.


## [1.0.11] - 2026-01-19

### Changed
- **Package Name**: Renamed Composer package from `sandipmandal/google-drive-uploader` to `open-php/google-drive-uploader` to align with the new branding.
- **Documentation**: Updated `README.md` with new installation instructions and badge links.

## [1.0.10] - 2026-01-16

### Added
- **Thumbnail Helper**: Added `getThumbnailLink($fileId, $width)` method. This provides an alternative way to load images (via `drive.google.com/thumbnail`) which is often less restrictive than the full-size download link.

### Changed
- **Embed Link**: Reverted `getEmbedLink` to the standard `drive.google.com/uc?export=view&id=...` format. This handles redirects to the correct content server better than direct CDN links in some regions.

### Fixed
- **API Response**: Fixed `uploadFiles` (bulk upload) to correctly request `mimeType` in the response fields.

### Changed
- **Image Reliability**: Updated `getEmbedLink` to use the `drive.google.com/thumbnail?sz=s2000&id=...` endpoint. This is the official and most reliable way to serve high-resolution (up to 2000px) direct images from Drive without 403 errors.
- **Bug Fix**: Re-applied the fix for `uploadFiles` (plural) to ensure `mimeType` is returned in the API response fields.

## [1.0.9] - 2026-01-16

### Fixed
- **API Response**: Added `mimeType` to the requested fields in `uploadFile` and `uploadFiles`. This ensures the returned `DriveFile` object correctly populates `$file->mimeType`, fixing issues where the type was null in post-upload logic.

## [1.0.8] - 2026-01-16

### Changed
- **Image Embedding**: Updated `getEmbedLink` to use the `lh3.googleusercontent.com` CDN domain. This is significantly more reliable for `<img>` tags than the previous `drive.google.com/uc` link, avoiding 403 errors and rate limits.
- **Preview Helper**: Added `getPreviewLink($fileId)` method to generate the `.../preview` URL used for embedding Videos and PDFs in `<iframe>` tags.

## [1.0.7] - 2026-01-16

### Added
- **Laravel Integration**: Added comprehensive Laravel integration guide to `README.md`.

### Fixed
- **Auth Flow**: Updated `getService` to strictly check for an access token before proceeding. If no token is found (missing or invalid `token.json`), it now throws a clear `Exception` ("Google Drive Auth Required") instead of letting the API call fail with a confusing 403 Forbidden error.
- **Cleanup**: Removed CLI echo messages from `refreshAccessToken` to prevent output pollution in web apps.

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
