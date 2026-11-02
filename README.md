# Google Drive Uploader Library

[![Latest Version on Packagist](https://img.shields.io/packagist/v/open-php/google-drive-uploader.svg?style=flat-square)](https://packagist.org/packages/open-php/google-drive-uploader)
[![Total Downloads](https://img.shields.io/packagist/dt/open-php/google-drive-uploader.svg?style=flat-square)](https://packagist.org/packages/open-php/google-drive-uploader)
[![License](https://img.shields.io/packagist/l/open-php/google-drive-uploader.svg?style=flat-square)](https://packagist.org/packages/open-php/google-drive-uploader)
[![CI](https://github.com/mrsandipmandal/google-drive-uploader/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/mrsandipmandal/google-drive-uploader/actions/workflows/ci.yml)

A simple, robust PHP library for uploading files to Google Drive. It handles creating folders, setting public permissions, and most importantly, **automatic token refreshing and retries** (self-healing auth) to handle the dreaded 7-day token expiry for testing apps.

## Features

- 📂 **Auto-create folders**: Automatically creates folders, including nested paths such as `Uploads/Photos`.
- 🔄 **Self-Healing Auth**: Automatically catches 401 errors, refreshes tokens using your refresh token, and retries the upload.
- 🌍 **Public Links**: Option to automatically make uploaded files public and get a web-viewable link.
- ⬇️ **Download files**: Download Drive files directly to a local path.
- 📦 **PSR-4 Compliant**: Namespace `Open\GoogleDrive`, ready for any Composer project.

## Installation

Install via Composer:

```bash
composer require open-php/google-drive-uploader
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
8. **Important**: While your OAuth consent screen is in "Testing" mode, refresh tokens for external users can expire after 7 days. Moving the app to production may change this behavior, subject to Google's OAuth policies. The library can refresh access tokens only while Google accepts the stored refresh token.

Keep `credentials.json` and `token.json` private. Do not commit them to source control or expose them through a public web directory; restrict filesystem access to the application account.

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
    // Slash-separated paths create nested folders when needed.
    $folderName = 'My Uploads/Photos';
    
    // Upload file
    $fileId = $drive->uploadFile($localFilePath, $folderName);
    
    // uploadFile returns a Google Drive file object.
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

### 5. Deleting a File

You can permanently delete a file using its File ID:

```php
try {
    $fileId = '12345abcde...';
    $drive->deleteFile($fileId);
    echo "File deleted successfully.";
} catch (Exception $e) {
    echo "Error deleting file: " . $e->getMessage();
}
```

### 6. Renaming a File

You can rename a file using its File ID:

```php
try {
    $fileId = '12345abcde...';
    $newName = 'My New Name.jpg';
    
    $updatedFile = $drive->renameFile($fileId, $newName);
    
    echo "File renamed to: " . $updatedFile->getName();
} catch (Exception $e) {
    echo "Error renaming file: " . $e->getMessage();
}
```

### 7. Downloading a File

Download a Drive file to a local path. The destination directory must already exist and be writable by PHP.

```php
try {
    $drive->downloadFile('GOOGLE_DRIVE_FILE_ID', '/path/to/downloads/report.pdf');
    echo "File downloaded.";
} catch (Exception $e) {
    echo "Error downloading file: " . $e->getMessage();
}
```

`downloadFile` requires Google Drive to provide a download link for the file.

## Laravel Integration

This package is framework-agnostic, but can be used in Laravel. The example below shows one way to connect the library's OAuth flow to Laravel routes.

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

## Disclaimer

This independent open-source project is not affiliated with, endorsed by, or sponsored by Google LLC. Google Drive and Google are trademarks of Google LLC.

You are responsible for securing OAuth credentials and tokens, configuring Google Cloud permissions, and complying with Google's applicable terms and policies. Do not expose credentials or tokens in public repositories, client-side code, or public directories.

## Author

- **Sandip Mandal** - [GitHub](https://github.com/mrsandipmandal)