<?php

namespace App\Services;

use App\Exceptions\DriveException;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Exception as GoogleServiceException;
use Illuminate\Support\Facades\Log;

class DriveClient
{
    private ?Client $client = null;
    private ?Drive $driveService = null;

    /**
    * Get (and refresh) an access token using the configured refresh token.
    */
    public function getAccessToken(): string
    {
        $clientId = config('services.google_drive.client_id');
        $clientSecret = config('services.google_drive.client_secret');
        $refreshToken = config('services.google_drive.refresh_token');
        if (!$clientId || !$clientSecret || !$refreshToken) {
            throw new DriveException('Google Drive credentials are not configured. Add client id, secret, and refresh token.', 'DRIVE_NOT_CONFIGURED');
        }

        $client = $this->getClient();
        $client->setRefreshToken($refreshToken);
        try {
            $token = $client->fetchAccessTokenWithRefreshToken($refreshToken);
        } catch (\Throwable $e) {
            throw new DriveException('Failed to refresh Google Drive access token.', 'DRIVE_AUTH_FAILED', previous: $e);
        }

        if (isset($token['error'])) {
            $message = $token['error_description'] ?? $token['error'] ?? 'Unknown error refreshing token';
            throw new DriveException($message, 'DRIVE_AUTH_FAILED');
        }

        $client->setAccessToken($token);

        return $token['access_token'] ?? '';
    }

    public function createFolder(string $name, ?string $parentId = null): array
    {
        $this->authenticate();
        $file = new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => $parentId ? [$parentId] : [],
        ]);

        try {
            $created = $this->getDriveService()->files->create(
                $file,
                ['fields' => 'id,name,webViewLink,mimeType,parents', 'supportsAllDrives' => true]
            );
        } catch (GoogleServiceException $e) {
            Log::error('Drive folder creation failed', ['error' => $e->getMessage()]);
            throw new DriveException('Unable to create folder on Drive.', 'DRIVE_FOLDER_CREATE_FAILED', previous: $e);
        }

        return [
            'id' => $created->id,
            'name' => $created->name,
            'web_view_link' => $created->webViewLink,
            'mime_type' => $created->mimeType,
            'parents' => $created->getParents(),
        ];
    }

    public function findFolderByName(string $name, ?string $parentId = null): ?array
    {
        $this->authenticate();
        $safeName = addslashes($name);
        $q = "mimeType='application/vnd.google-apps.folder' and name='{$safeName}' and trashed=false";
        if ($parentId) {
            $q .= " and '$parentId' in parents";
        }

        try {
            $list = $this->getDriveService()->files->listFiles([
                'q' => $q,
                'fields' => 'files(id,name,webViewLink,mimeType,parents)',
                'pageSize' => 1,
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true,
            ]);
        } catch (GoogleServiceException $e) {
            Log::warning('Drive folder search failed', ['error' => $e->getMessage()]);
            return null;
        }

        if (empty($list->files)) {
            return null;
        }

        $folder = $list->files[0];

        return [
            'id' => $folder->id,
            'name' => $folder->name,
            'web_view_link' => $folder->webViewLink,
            'mime_type' => $folder->mimeType,
            'parents' => $folder->getParents(),
        ];
    }

    public function ensureFolder(string $name, ?string $parentId = null): array
    {
        $existing = $this->findFolderByName($name, $parentId);
        if ($existing) {
            return $existing;
        }

        return $this->createFolder($name, $parentId);
    }

    public function uploadFile(string $folderId, string $filePath, string $fileName, ?string $mimeType = null): array
    {
        $this->authenticate();

        if (!is_readable($filePath)) {
            throw new DriveException('File not readable for upload.', 'DRIVE_UPLOAD_FAILED');
        }

        $fileMetadata = new DriveFile([
            'name' => $fileName,
            'parents' => [$folderId],
        ]);

        try {
            $file = $this->getDriveService()->files->create(
                $fileMetadata,
                [
                    'data' => file_get_contents($filePath),
                    'mimeType' => $mimeType ?: 'application/octet-stream',
                    'uploadType' => 'multipart',
                    'fields' => 'id,name,webViewLink,mimeType,parents',
                    'supportsAllDrives' => true,
                ]
            );
        } catch (GoogleServiceException $e) {
            Log::error('Drive upload failed', ['error' => $e->getMessage()]);
            throw new DriveException('Drive upload failed.', 'DRIVE_UPLOAD_FAILED', previous: $e);
        }

        return [
            'id' => $file->id,
            'name' => $file->name,
            'web_view_link' => $file->webViewLink,
            'mime_type' => $file->mimeType,
            'parents' => $file->getParents(),
        ];
    }

    public function getFile(string $fileId): array
    {
        $this->authenticate();

        try {
            $file = $this->getDriveService()->files->get($fileId, [
                'fields' => 'id,name,webViewLink,mimeType,parents',
                'supportsAllDrives' => true,
            ]);
        } catch (GoogleServiceException $e) {
            $status = $e->getCode();
            if ($status === 404) {
                throw new DriveException('Drive item not found.', 'DRIVE_ITEM_NOT_FOUND', previous: $e);
            }

            Log::error('Drive fetch failed', ['error' => $e->getMessage()]);
            throw new DriveException('Unable to fetch Drive item.', 'DRIVE_FETCH_FAILED', previous: $e);
        }

        return [
            'id' => $file->id,
            'name' => $file->name,
            'web_view_link' => $file->webViewLink,
            'mime_type' => $file->mimeType,
            'parents' => $file->getParents(),
        ];
    }

    private function authenticate(): void
    {
        $this->getAccessToken();
    }

    private function getClient(): Client
    {
        if ($this->client) {
            return $this->client;
        }

        $client = new Client();
        $client->setApplicationName(config('services.google_drive.app_name', 'PO-Assist'));
        $client->setClientId(config('services.google_drive.client_id'));
        $client->setClientSecret(config('services.google_drive.client_secret'));
        $client->setRedirectUri(config('services.google_drive.redirect_uri'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setScopes([config('services.google_drive.scope', Drive::DRIVE_FILE)]);

        $this->client = $client;

        return $client;
    }

    private function getDriveService(): Drive
    {
        if ($this->driveService) {
            return $this->driveService;
        }

        $this->driveService = new Drive($this->getClient());

        return $this->driveService;
    }
}
