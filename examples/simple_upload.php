<?php
// Adjust autoload path based on where the script is run
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    // Fallback if installed as a dependency in another project
    require __DIR__ . '/../../../vendor/autoload.php';
}

use Open\GoogleDrive\GoogleDriveService;

// Helper to find config files
function findConfigFile($filename) {
    $paths = [
        __DIR__ . '/../' . $filename,       // Library root
        __DIR__ . '/../../' . $filename,    // Project root (dev env)
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            return realpath($path);
        }
    }
    return null;
}

$credentialsInfo = findConfigFile('credentials.json');
$tokenInfo = findConfigFile('token.json');

if (!$credentialsInfo || !$tokenInfo) {
    die("❌ Error: credentials.json or token.json not found.\nPlease place them in the library root or project root.\n");
}
             


// Initialize Service
$drive = new GoogleDriveService($credentialsInfo, $tokenInfo);

// Check if file provided
if (!isset($argv) || count($argv) < 2) {
    echo "Usage: php simple_upload.php <file_path>\n";
    echo "Example: php simple_upload.php image.jpg\n";
    exit(1);
}

$filePath = $argv[1];

if (!file_exists($filePath)) {
    die("Error: File not found: $filePath\n");
}

try {
    echo "Uploading " . basename($filePath) . "...\n";
    
    // Upload file to "MyUploads" folder (will create if not exists)
    $file = $drive->uploadFile($filePath, "MyUploads");
    
    echo "✅ Success!\n";
    echo "File ID: " . $file->getId() . "\n";
    echo "View Link: " . $file->getWebViewLink() . "\n";

    // specific check for images to provide an embeddable link
    $mimeType = mime_content_type($filePath);
    if (strpos($mimeType, 'image/') === 0) {
        $directLink = "https://drive.google.com/uc?export=view&id=" . $file->getId();
        echo "Image Source (src): " . $directLink . "\n";
        echo "HTML Tag: <img src=\"" . $directLink . "\" width=\"200\" />\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
