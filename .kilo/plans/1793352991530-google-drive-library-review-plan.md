# Plan: Google Drive Uploader Library Review & Updates

## Goal
Confirm the library is updated/relevant for upload, delete, view, image-tag link generation, and download; implement small improvements where gaps exist.

## Context
Repo: `/var/www/html/google-drive-uploader` (Packagist `open-php/google-drive-uploader`)
- Single source: `src/GoogleDriveService.php`
- Examples: `examples/simple_upload.php`, `delete_file.php`, `rename_file.php`
- Dependency: `google/apiclient: ^2.15` (official, actively maintained PHP client)
- PHP: `>=7.4`

## Feature coverage (verified against source)
| Feature | Method | Status |
|---|---|---|
| Upload | `uploadFile()`, `uploadFiles()` | Covered (multipart, auto-folder creation) |
| Delete | `deleteFile()` | Covered (permanent delete by file ID) |
| View link | `getWebViewLink()` (field returned) | Covered |
| Image tag `<img src>` | `getEmbedLink()` → `drive.google.com/uc?export=view&id=` | Covered |
| Thumbnail | `getThumbnailLink($id, $width)` | Covered |
| Preview (video/PDF iframe) | `getPreviewLink()` | Covered |
| Download | `webContentLink` field requested; direct `uc?export=download&id=` usable | Partial (no dedicated download helper) |
| Auth / token refresh | `refreshAccessToken()`, `callWithRetry()` (401 → refresh → retry) | Covered |
| Public permissions | `setPublicPermissions()` | Covered |

## Decisions
1. **Dependency**: Keep `google/apiclient: ^2.15`. It is current; no change needed unless user wants `^2.x` relaxed/pinned via `composer.lock`.
2. **Download**: Add a `downloadFile(string $fileId, string $destPath): void` method that streams `webContentLink`/`uc?export=download&id=` to a local file. This is the only genuinely missing capability.
3. **Embed link format**: Lock `getEmbedLink()` to `https://drive.google.com/uc?export=view&id={id}` (current, simplest, works for public files). No thumbnail/CDN flip-flopping.
4. **Tests**: Add a minimal PHPUnit test suite (auth is hard to unit-test without real credentials; focus on pure helpers: link generators, metadata, and retry behavior with mocks).
5. **composer.lock**: Leave gitignored (library convention); document that consumers run `composer install`.

## Risks
- `getEmbedLink` returns a Google redirect URL; may 403 for non-public files — documented behavior.
- Download via `webContentLink` may require the file to be public or the access token to be valid; method should use the authenticated client.
- No CI in repo; tests would run locally only.

## Open questions (for implementation agent / user)
- Should `downloadFile` accept a custom filename, or derive it from the Drive file metadata?
- Do you want GitHub Actions added to run tests on push?

## Validation steps
1. `composer install` / `composer validate`
2. Run PHPUnit tests (if added)
3. Manual smoke: upload → get embed link → `<img src>` renders → delete file
4. Confirm `downloadFile` writes bytes to local disk correctly

## Affected files
- `src/GoogleDriveService.php` — add `downloadFile()`
- `tests/` — new test directory (if PHPUnit added)
- `composer.json` — add `phpunit/phpunit` as dev dependency (if tests added)
