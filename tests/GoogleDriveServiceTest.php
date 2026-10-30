<?php

use Open\GoogleDrive\GoogleDriveService;
use Google\Client;
use Google\Service\Drive;
use PHPUnit\Framework\TestCase;

class GoogleDriveServiceTest extends TestCase
{
    public function testGetEmbedLinkReturnsViewUrl(): void
    {
        $service = $this->getServiceWithoutAuth();
        $this->assertSame(
            'https://drive.google.com/uc?export=view&id=abc123',
            $service->getEmbedLink('abc123')
        );
    }

    public function testGetPreviewLinkReturnsPreviewUrl(): void
    {
        $service = $this->getServiceWithoutAuth();
        $this->assertSame(
            'https://drive.google.com/file/d/abc123/preview',
            $service->getPreviewLink('abc123')
        );
    }

    public function testGetThumbnailLinkReturnsThumbnailUrl(): void
    {
        $service = $this->getServiceWithoutAuth();
        $this->assertSame(
            'https://drive.google.com/thumbnail?sz=w200&id=abc123',
            $service->getThumbnailLink('abc123')
        );
    }

    public function testGetThumbnailLinkCustomWidth(): void
    {
        $service = $this->getServiceWithoutAuth();
        $this->assertSame(
            'https://drive.google.com/thumbnail?sz=w500&id=abc123',
            $service->getThumbnailLink('abc123', 500)
        );
    }

    private function getServiceWithoutAuth(): GoogleDriveService
    {
        $credentials = tempnam(sys_get_temp_dir(), 'gdrive_creds');
        file_put_contents($credentials, json_encode([
            'installed' => [
                'client_id' => 'test-client-id',
                'client_secret' => 'test-client-secret',
                'redirect_uris' => ['urn:ietf:wg:oauth:2.0:oob'],
            ],
        ]));

        $token = tempnam(sys_get_temp_dir(), 'gdrive_token');
        file_put_contents($token, json_encode([
            'access_token' => 'ya29.test-access-token',
            'refresh_token' => '1//test-refresh-token',
            'expires_in' => 3600,
            'created' => time(),
        ]));

        $service = new GoogleDriveService($credentials, $token);

        unlink($credentials);
        unlink($token);

        return $service;
    }
}
