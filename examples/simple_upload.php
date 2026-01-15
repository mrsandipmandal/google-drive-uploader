<?php
require 'vendor/autoload.php';

use SandipMandal\GoogleDrive\GoogleDriveService;

// Configuration
$credentialsInfo = 'credentials.json'; // Path to your credentials
$tokenInfo = 'token.json';             // Path to save/read token

// Initialize Service
$drive = new GoogleDriveService($credentialsInfo, $tokenInfo);

// Check if file provided
if ($argc < 2) {
    echo "Usage: php simple_upload.php <file_path>\n";
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

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
