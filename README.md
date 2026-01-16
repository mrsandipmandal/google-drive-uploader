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
    echo "Web View Link: " . $fileId->getWebViewLink() . "\n";
    
    // For images (<img src="...">), use the embed link:
    echo "Embed Link: " . $drive->getEmbedLink($fileId->id);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

```

### 4. Uploading Multiple Files

To upload efficiently (checking folder existence only once):

```php
$files = ['/path/to/img1.jpg', '/path/to/img2.png'];
$uploadedFiles = $drive->uploadFiles($files, 'My Holiday');

foreach ($uploadedFiles as $path => $file) {
    echo "Uploaded $path -> ID: " . $file->id . "\n";
}
```

## Laravel Integration

This package is framework-agnostic, but can be easily used in Laravel.
This package handles the entire OAuth flow. Here is a complete production-ready implementation.

### 1. Define Routes

In `routes/web.php`:

```php
use App\Http\Controllers\DriveController;

Route::post('/upload', [DriveController::class, 'upload'])->name('google.upload');
Route::get('/google/callback', [DriveController::class, 'callback'])->name('google.callback');
```

> [!IMPORTANT] 
> **Google Cloud Console Setup**
> You MUST add the full callback URL to your **Authorized redirect URIs** in the Google Cloud Console.
>
> If your app runs at `http://localhost:8000`, add:
> `http://localhost:8000/google/callback`
>
> If your app runs at `http://localhost/myapp/public`, add:
> `http://localhost/myapp/public/google/callback`
>
> **The URL must match EXACTLY what your Laravel app generates.**

### 2. The Controller

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Open\GoogleDrive\GoogleDriveService;
use Exception;

class DriveController extends Controller
{
    private function getDriveService()
    {
        return new GoogleDriveService(
            storage_path('app/google/credentials.json'),
            storage_path('app/google/token.json')
        );
    }

    public function upload(Request $request)
    {
        $request->validate(['file' => 'required|file']);

        try {
            $drive = $this->getDriveService();

            $uploadedFile = $request->file('file');
            
            $googleFile = $drive->uploadFile(
                $uploadedFile->getPathname(), 
                'LaravelUploads',
                true,
                $uploadedFile->getClientOriginalName()
            );
            
            return response()->json([
                'status' => 'success',
                'url' => $googleFile->getWebViewLink(),
                'embed_link' => $drive->getEmbedLink($googleFile->id)
            ]);

        } catch (Exception $e) {
            // Check for "Auth Required" message from library
            if (str_contains($e->getMessage(), 'Google Drive Auth Required')) {
                // Generate Login URL with the callback route
                // IMPORTANT: This route() must match the URI in Google Console
                $authUrl = $this->getDriveService()->getAuthUrl(route('google.callback'));
                return redirect($authUrl);
            }
            throw $e;
        }
    }

    public function callback(Request $request)
    {
        $code = $request->input('code');
        if (!$code) {
             return response()->json(['error' => 'No code provided'], 400);
        }

        $drive = $this->getDriveService();
        
        // Exchange code for token and save it automatically
        $drive->authenticate($code, route('google.callback'));

        return "Token saved! You can now try uploading again.";
    }
}
```

## How to Display Files (Blade Example)

Different file types require different HTML tags.

**Controller:** Pass the `id` and `mimeType` to your view.
```php
return view('googleFileView', [
    'fileId' => $googleFile->id,
    'mimeType' => $googleFile->mimeType,
    'embedLink' => $drive->getEmbedLink($googleFile->id), // For Full Images
    'thumbnailLink' => $drive->getThumbnailLink($googleFile->id, 500), // For Smaller/Safer Images
    'previewLink' => $drive->getPreviewLink($googleFile->id) // For Videos/PDFs
]);
```

**View (`googleFileView.blade.php`):**

```blade
<!-- If it is an IMAGE -->
@if(str_contains($mimeType, 'image/'))
    <!-- Try High-Res Embed, fallback to Thumbnail if blocked -->
    <img src="{{ $embedLink }}" 
         onerror="this.onerror=null;this.src='{{ $thumbnailLink }}';" 
         alt="Image from Drive">

<!-- If it is a VIDEO -->
@elseif(str_contains($mimeType, 'video/'))
    <iframe src="{{ $previewLink }}" width="640" height="480"></iframe>

<!-- If it is a PDF -->
@elseif($mimeType == 'application/pdf')
    <iframe src="{{ $previewLink }}" width="600" height="800"></iframe>

<!-- Download Link for others -->
@else
    <a href="https://drive.google.com/uc?export=download&id={{ $fileId }}" class="btn btn-primary">Download File</a>
@endif
```

## Support

- **Issues**: [GitHub Issues](https://github.com/mrsandipmandal/google-drive-uploader/issues)
- **Source**: [GitHub Repository](https://github.com/mrsandipmandal/google-drive-uploader)

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) file for details.

## Author

- **Sandip Mandal** - [GitHub](https://github.com/mrsandipmandal)