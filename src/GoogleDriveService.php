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

        file_put_contents($this->tokenPath, json_encode($token));
        $this->client->setAccessToken($token);
        return $token;
    }

    /**
     * Upload a file to a specific folder.
     * 
     * @param string $filePath Local path to the file
     * @param string $folderName Name of the folder to upload to (created if not exists)
     * @param bool $makePublic Whether to make the file public
     * @return Drive\DriveFile The uploaded file object
     */
    public function uploadFile(string $filePath, string $folderName, bool $makePublic = true): Drive\DriveFile
    {
        return $this->callWithRetry(function () use ($filePath, $folderName, $makePublic) {
            $service = $this->getService();
            
            // Get or create parent folder
            $folderId = $this->getOrCreateFolder($folderName);

            $fileMetadata = new Drive\DriveFile([
                'name' => basename($filePath),
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

            return $file;
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
     * Note: This link format is widely used for hosting images from Drive.
     *
     * @param string $fileId
     * @return string
     */
    public function getEmbedLink(string $fileId): string
    {
        return "https://drive.google.com/uc?export=view&id=" . $fileId;
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
            // No token file; caller should handle auth flow (usually checks before calling this)
            // But if we are here mostly likely we expect it to exist.
             if (php_sapi_name() === 'cli') {
                 echo "Error: Token file not found at {$this->tokenPath}\n";
             }
             return; 
        }

        $token = json_decode(file_get_contents($this->tokenPath), true);
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
                file_put_contents($this->tokenPath, json_encode($merged));
                $this->client->setAccessToken($merged);
            }
        }
    }
}
