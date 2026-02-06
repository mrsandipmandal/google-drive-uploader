<?php

require __DIR__ . '/../vendor/autoload.php';

use Open\GoogleDrive\GoogleDriveService;

// Load configuration
// Ensure you have credentials.json and token.json in the project root or specified paths
$credentialsPath = __DIR__ . '/../credentials.json';
$tokenPath = __DIR__ . '/../token.json';

try {
    // Initialize the service
    $driveService = new GoogleDriveService($credentialsPath, $tokenPath);

    // Get the file ID from command line argument
    if ($argc < 2) {
        echo "Usage: php delete_file.php <file_id>\n";
        exit(1);
    }

    $fileId = $argv[1];

    echo "Attempting to delete file with ID: $fileId...\n";

    // Delete the file
    $driveService->deleteFile($fileId);

    echo "File deleted successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
