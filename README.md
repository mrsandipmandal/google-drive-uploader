# Google Drive Uploader Library

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sandipmandal/google-drive-uploader.svg?style=flat-square)](https://packagist.org/packages/sandipmandal/google-drive-uploader)
[![Total Downloads](https://img.shields.io/packagist/dt/sandipmandal/google-drive-uploader.svg?style=flat-square)](https://packagist.org/packages/sandipmandal/google-drive-uploader)
[![License](https://img.shields.io/packagist/l/sandipmandal/google-drive-uploader.svg?style=flat-square)](https://packagist.org/packages/sandipmandal/google-drive-uploader)

A simple, robust PHP library for uploading files to Google Drive. It handles creating folders, setting public permissions, and most importantly, **automatic token refreshing and retries** (self-healing auth) to handle the dreaded 7-day token expiry for testing apps.

## Features

- 📂 **Auto-create folders**: Automatically creates folders if they don't exist.
- 🔄 **Self-Healing Auth**: Automatically catches 401 errors, refreshes tokens using your refresh token, and retries the upload.
- 🌍 **Public Links**: Option to automatically make uploaded files public and get a web-viewable link.
- 📦 **PSR-4 Compliant**: Namespace `Open\GoogleDrive`, ready for any Composer project.

## Installation

Install via Composer:

```bash
composer require sandipmandal/google-drive-uploader
```

## Google Cloud Setup

To use this library, you need credentials from the Google Cloud Console.

1.  Go to [Google Cloud Console](https://console.cloud.google.com/).
2.  Create a Project.
3.  Enable **Google Drive API**.
4.  Go to **Credentials** -> **Create Credentials** -> **OAuth Client ID**.
5.  Application Type: **Web Application**.
6.  Redirect URI: `http://localhost/callback.php` (or your production URL).
7.  Download the JSON and save it as `credentials.json`.
8.  **Important**: If your app is in "Testing" mode, tokens expire in 7 days. Ideally, set Publishing Status to **"In Production"** to avoid this, but this library handles auto-refresh if valid refresh tokens are present.

## Usage

### 1. Initialization

```php
use Open\GoogleDrive\GoogleDriveService;

$credentialsPath = 'path/to/credentials.json';
$tokenPath = 'path/to/token.json'; // This file will be created/updated automatically

$drive = new GoogleDriveService($credentialsPath, $tokenPath);
```

### 2. First-Time Authentication

If you don't have a `token.json` yet, you need to authorize the app.

```php
if (!file_exists($tokenPath)) {
    // Generate Auth URL
    $authUrl = $drive->getAuthUrl('http://localhost/callback.php');
    echo "Open this URL in your browser:\n$authUrl\n";
    
    // After user grants permission, Google redirects to your callback with a ?code=...
    // Exchange code for token:
    // $code = $_GET['code'];
    // $drive->authenticate($code); // This saves the token to $tokenPath
}
```

### 3. Uploading a File

```php
try {
    $localFilePath = '/path/to/image.jpg';
    $folderName = 'My Uploads';
    
    // Upload file
    $fileId = $drive->uploadFile($localFilePath, $folderName);
    
    // Get public accessible link (if enabled in service)
    // Note: ensure uploadFile returns the file object or ID based on your specific version needs
    // The current implementation returns a Google_Service_Drive_DriveFile object
    
    echo "File Uploaded! ID: " . $fileId->id . "\n";
    echo "Web View Link: " . $fileId->getWebViewLink();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Laravel Integration

This package is framework-agnostic, but can be easily used in Laravel.

**Controller Example:**

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Open\GoogleDrive\GoogleDriveService;

class DriveController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate(['file' => 'required|file']);

        $drive = new GoogleDriveService(
            storage_path('app/google/credentials.json'),
            storage_path('app/google/token.json')
        );

        $uploadedFile = $request->file('file');
        $googleFile = $drive->uploadFile(
            $uploadedFile->getPathname(), 
            'LaravelUploads'
        );
        
        return response()->json([
            'message' => 'Upload successful',
            'url' => $googleFile->getWebViewLink()
        ]);
    }
}
```

## Support

- **Issues**: [GitHub Issues](https://github.com/mrsandipmandal/google-drive-uploader/issues)
- **Source**: [GitHub Repository](https://github.com/mrsandipmandal/google-drive-uploader)

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) file for details.

## Author

- **Sandip Mandal** - [GitHub](https://github.com/mrsandipmandal)