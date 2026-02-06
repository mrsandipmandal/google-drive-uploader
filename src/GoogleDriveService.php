<?php
/**
 * @author Sandip Mandal
 * @link   https://mrsandipmandal.github.io
 * @repo   https://github.com/mrsandipmandal/google-drive-uploader.git
 */

namespace Open\GoogleDrive;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Exception as GoogleServiceException;
use Exception;

class GoogleDriveService
{
    private $client;
    private $service;
    private $tokenPath;
    private $credentialsPath;

    /**
     * @param string $credentialsPath Path to credentials.json
     * @param string $tokenPath Path to token.json
     */
    public function __construct(string $credentialsPath, string $tokenPath)
    {
        $this->credentialsPath = $credentialsPath;
        $this->tokenPath = $tokenPath;

        $this->client = new Client();
        $this->client->setAuthConfig($credentialsPath);
        $this->client->addScope(Drive::DRIVE_FILE);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent'); // Force prompt to ensure refresh token
    }

    /**
     * Get the service instance, handling initialization and auth.
     * 
     * @return Drive
     * @throws Exception If auth fails
     */
    public function getService(): Drive
    {
        if ($this->service) {
            return $this->service;
        }

        $this->refreshAccessToken(); // Ensure valid token before creating service
        
        if (!$this->client->getAccessToken()) {
            throw new Exception("Google Drive Auth Required: Token file missing or invalid. Please authenticate.");
        }

        $this->service = new Drive($this->client);
        return $this->service;
    }

    /**
     * Get the Client instance (useful for raw operations or auth flow).
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Generate the authorization URL for the user to log in.
     * @param string $redirectUri
     * @return string
     */
    public function getAuthUrl(string $redirectUri): string
    {
        $this->client->setRedirectUri($redirectUri);
        return $this->client->createAuthUrl();
    }

    /**
     * Exchange authorization code for a token and save it.
     * @param string $code
     * @param string $redirectUri
     * @return array The token array
     */
    public function authenticate(string $code, string $redirectUri): array
    {
        $this->client->setRedirectUri($redirectUri);
        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new Exception("Error fetching access token: " . ($token['error_description'] ?? $token['error']));
        }

        $this->saveToken($token);
        $this->client->setAccessToken($token);
        return $token;
    }

    /**
     * Save token to file, creating directory if needed.
     */
    private function saveToken(array $token): void
    {
        $dir = dirname($this->tokenPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->tokenPath, json_encode($token));
    }

    /**
     * Upload a file to a specific folder.
     * 
     * @param string $filePath Local path to the file
     * @param string $folderName Name of the folder to upload to (created if not exists)
     * @param bool $makePublic Whether to make the file public
     * @param string|null $fileName Custom file name (optional, defaults to basename of path)
     * @return Drive\DriveFile The uploaded file object
     */
    /**
     * Upload a file to a specific folder.
     * 
     * @param string $filePath Local path to the file
     * @param string $folderName Name of the folder to upload to (created if not exists)
     * @param bool $makePublic Whether to make the file public
     * @param string|null $fileName Custom file name (optional, defaults to basename of path)
     * @return Drive\DriveFile The uploaded file object
     */
    public function uploadFile(string $filePath, string $folderName, bool $makePublic = true, ?string $fileName = null): Drive\DriveFile
    {
        return $this->callWithRetry(function () use ($filePath, $folderName, $makePublic, $fileName) {
            $service = $this->getService();
            
            // Get or create parent folder
            $folderId = $this->getOrCreateFolder($folderName);

            $fileMetadata = new Drive\DriveFile([
                'name' => $fileName ?? basename($filePath),
                'parents' => [$folderId]
            ]);

            $content = file_get_contents($filePath);
            $mimeType = mime_content_type($filePath);

            $file = $service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'supportsAllDrives' => true,
                'fields' => 'id, name, mimeType, parents, webViewLink, webContentLink, thumbnailLink'
            ]);

            if ($makePublic) {
                $this->setPublicPermissions($file->getId());
            }

            return $file;
        });
    }

    /**
     * Upload multiple files to a specific folder efficiently.
     * This optimizes by only checking/creating the folder once.
     * 
     * @param array $filePaths Array of local file paths
     * @param string $folderName Name of the folder to upload to
     * @param bool $makePublic Whether to make files public
     * @return array Array of uploaded file objects (keys are original paths)
     */
    public function uploadFiles(array $filePaths, string $folderName, bool $makePublic = true): array
    {
        return $this->callWithRetry(function () use ($filePaths, $folderName, $makePublic) {
            $service = $this->getService();
            
            // Optimization: Get folder ID once for all files
            $folderId = $this->getOrCreateFolder($folderName);
            
            $results = [];

            foreach ($filePaths as $key => $value) {
                // Support ['filename.jpg' => '/path/to/tmp'] or ['/path/to/file']
                $filePath = $value;
                $fileName = is_string($key) ? $key : basename($filePath);

                if (!file_exists($filePath)) {
                    continue;
                }

                $fileMetadata = new Drive\DriveFile([
                    'name' => $fileName,
                    'parents' => [$folderId]
                ]);

                $content = file_get_contents($filePath);
                $mimeType = mime_content_type($filePath);

                $file = $service->files->create($fileMetadata, [
                    'data' => $content,
                    'mimeType' => $mimeType,
                    'uploadType' => 'multipart',
                    'supportsAllDrives' => true,
                    'fields' => 'id, name, parents, webViewLink, webContentLink, thumbnailLink'
                ]);

                if ($makePublic) {
                    $this->setPublicPermissions($file->getId());
                }

                $results[$fileName] = $file;
            }

            return $results;
        });
    }

    /**
     * Find or create a folder by name.
     */
    public function getOrCreateFolder(string $folderName, ?string $parentId = null): string
    {
        return $this->callWithRetry(function () use ($folderName, $parentId) {
            $service = $this->getService();
            $escapedName = str_replace("'", "\\'", $folderName);
            $query = "mimeType='application/vnd.google-apps.folder' and name='{$escapedName}' and trashed=false";
            
            if ($parentId) {
                $query .= " and '{$parentId}' in parents";
            }

            $response = $service->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name)',
                'includeItemsFromAllDrives' => true,
                'supportsAllDrives' => true,
            ]);

            $folders = $response->getFiles();
            if (count($folders) > 0) {
                return $folders[0]->getId();
            }

            // Create if not exists
            $folderMetadata = new Drive\DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder'
            ]);
            if ($parentId) {
                $folderMetadata->setParents([$parentId]);
            }

            $folder = $service->files->create($folderMetadata, ['fields' => 'id']);
            return $folder->getId();
        });
    }

    /**
     * Delete a file permanently from Google Drive.
     * 
     * @param string $fileId The ID of the file to delete
     * @return void
     * @throws Exception If deletion fails
     */
    public function deleteFile(string $fileId): void
    {
        $this->callWithRetry(function () use ($fileId) {
            $this->getService()->files->delete($fileId);
        });
    }

    /**
     * Rename a file in Google Drive.
     * 
     * @param string $fileId The ID of the file to rename
     * @param string $newName The new name for the file
     * @return Drive\DriveFile The updated file object
     * @throws Exception If rename fails
     */
    public function renameFile(string $fileId, string $newName): Drive\DriveFile
    {
        return $this->callWithRetry(function () use ($fileId, $newName) {
            $fileMetadata = new Drive\DriveFile([
                'name' => $newName
            ]);
            
            return $this->getService()->files->update($fileId, $fileMetadata, [
                'fields' => 'id, name, parents, webViewLink, webContentLink'
            ]);
        });
    }

    /**
     * Make a file public (anyone with link can read).
     */
    public function setPublicPermissions(string $fileId): void
    {
        $this->callWithRetry(function () use ($fileId) {
            $permission = new Drive\Permission([
                'type' => 'anyone',
                'role' => 'reader'
            ]);
            $this->getService()->permissions->create($fileId, $permission);
        });
    }

    /**
     * Get a direct embeddable link (e.g., for <img src>).
     * Note: If this fails, ensure the file is 'Public'.
     *
     * @param string $fileId
     * @return string
     */
    public function getEmbedLink(string $fileId): string
    {
        return "https://drive.google.com/uc?export=view&id=" . $fileId;
    }

    /**
     * Get a preview link (e.g., for <iframe src>).
     * Suitable for Videos and PDFs.
     * 
     * @param string $fileId
     * @return string
     */
    public function getPreviewLink(string $fileId): string
    {
        return "https://drive.google.com/file/d/" . $fileId . "/preview";
    }

    /**
     * Get a thumbnail link (useful for previews/lists).
     * 
     * @param string $fileId
     * @param int $width Warning: anything > 200 may require auth cookies in some cases
     * @return string
     */
    public function getThumbnailLink(string $fileId, int $width = 200): string
    {
        return "https://drive.google.com/thumbnail?sz=w{$width}&id=" . $fileId;
    }

    /**
     * Execute a callback with retry logic for 401 errors.
     */
    private function callWithRetry(callable $callback)
    {
        try {
            return $callback();
        } catch (GoogleServiceException $e) {
            if ($e->getCode() == 401) {
                $this->refreshAccessToken(true); // Force refresh
                return $callback(); // Retry once
            }
            throw $e;
        }
    }

    /**
     * Load, check, and refresh the access token.
     * @param bool $force Force a refresh even if not strictly expired (for retry logic)
     */
    private function refreshAccessToken(bool $force = false): void
    {
        if (!file_exists($this->tokenPath)) {
            // No token file. Caller must handle auth flow.
             return; 
        }

        $content = file_get_contents($this->tokenPath);
        $token = json_decode($content, true);

        // Check if token file is empty or invalid JSON
        if (!$token || !is_array($token)) {
             // Invalid token file. Treat as not logged in.
             return;
        }

        $this->client->setAccessToken($token);

        if ($force || $this->client->isAccessTokenExpired()) {
            $refreshToken = $this->client->getRefreshToken();
            
            // Fallback to saved refresh token if client doesn't have it
            if (empty($refreshToken) && isset($token['refresh_token'])) {
                $refreshToken = $token['refresh_token'];
            }

            if ($refreshToken) {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                
                if (isset($newToken['error'])) {
                     throw new Exception("Error refreshing token: " . ($newToken['error_description'] ?? $newToken['error']));
                }

                // Preserve refresh token if not returned
                if (!isset($newToken['refresh_token'])) {
                    $newToken['refresh_token'] = $refreshToken;
                }

                $merged = array_merge($token, $newToken);
                $this->saveToken($merged);
                $this->client->setAccessToken($merged);
            }
        }
    }
}
