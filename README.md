# Google Drive Uploader Library

A simple, robust PHP library for uploading files to Google Drive. It handles creating folders, setting public permissions, and most importantly, **automatic token refreshing and retries** (self-healing auth).

## Features
- 📂 **Auto-create folders**: Just pass a folder name.
- 🔄 **Self-Healing Auth**: Automatically catches 401 errors, refreshes tokens, and retries.
- 🌍 **Public Links**: Option to automatically make uploaded files public.
- 📦 **PSR-4 Compliant**: Easy to drop into any composer project.

## Installation

### 1. Require via Composer
Since this is a private/local usage, add the repository to your project's `composer.json`:

```json
"repositories": [
    {
        "type": "path",
        "url": "./google-drive-uploader"
    }
],
"require": {
    "sandipmandal/google-drive-uploader": "@dev"
}
```

Then run:
```bash
composer update
```

## Google Cloud Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/).
2. Create a Project.
3. Enable **Google Drive API**.
4. Go to **Credentials** -> **Create Credentials** -> **OAuth Client ID**.
5. Application Type: **Web Application**.
6. Redirect URI: `http://localhost/path/to/your/callback.php` (or your production URL).
7. Download the JSON and save it as `credentials.json`.
8. **Important**: Set Publishing Status to **"In Production"** to avoid 7-day token expiry.

## Usage

### Initialization
```php
use Open\GoogleDrive\GoogleDriveService;

$drive = new GoogleDriveService('path/to/credentials.json', 'path/to/token.json');
```

### Uploading a File
```php
$file = $drive->uploadFile('/path/to/image.jpg', 'UploadsFolder');
echo "File URL: " . $file->getWebViewLink();
```

### Creating Auth URL (for first-time login)
```php
$url = $drive->getAuthUrl('http://localhost/callback.php');
header("Location: $url");
```

## Laravel Integration

Since this package is local, the best way to use it in Laravel is:

1.  **Create a folder** in your Laravel root called `packages/sandipmandal`.
2.  **Copy** the `google-drive-uploader` folder into `packages/sandipmandal/`.
3.  **Edit** `composer.json` in your Laravel project:

```json
"repositories": [
    {
        "type": "path",
        "url": "./packages/sandipmandal/google-drive-uploader"
    }
],
"require": {
    "sandipmandal/google-drive-uploader": "@dev"
}
```

4.  Run `composer update`.

### usage in a Controller
```php
use Open\GoogleDrive\GoogleDriveService;

class DriveController extends Controller
{
    public function upload(Request $request)
    {
        $drive = new GoogleDriveService(
            storage_path('app/google-drive/credentials.json'), 
            storage_path('app/google-drive/token.json')
        );

        $file = $drive->uploadFile($request->file('image')->getPathname(), 'MyUploads');
        
        return response()->json([
            'url' => $file->getWebViewLink()
        ]);
    }
}
```

## License
MIT
