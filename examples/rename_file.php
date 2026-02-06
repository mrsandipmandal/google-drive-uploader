<?php

require __DIR__ . '/../vendor/autoload.php';

use Open\GoogleDrive\GoogleDriveService;

// Load configuration
$credentialsPath = __DIR__ . '/../credentials.json';
$tokenPath = __DIR__ . '/../token.json';

try {
    $driveService = new GoogleDriveService($credentialsPath, $tokenPath);

    if ($argc < 3) {
        echo "Usage: php rename_file.php <file_id> <new_name>\n";
        exit(1);
    }

    $fileId = $argv[1];
    $newName = $argv[2];

    echo "Renaming file ID: $fileId to '$newName'...\n";

    $updatedFile = $driveService->renameFile($fileId, $newName);

    echo "File renamed successfully!\n";
    echo "New Name: " . $updatedFile->getName() . "\n";
    echo "ID: " . $updatedFile->getId() . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
