<?php
// app/Services/GoogleDriveService.php

namespace Mg\Pessoa;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

class PessoaGoogleDriveService
{
    protected Drive $driveService;

    public function __construct()
    {
        $client = new Client();
        $client->setAuthConfig(base_path(env('GOOGLE_DRIVE_CREDENTIALS_PATH')));
        $client->addScope(Drive::DRIVE);

        $this->driveService = new Drive($client);
    }

    /**
     * Cria uma pasta no Drive
     */
    public function createFolder(string $name, string $parentFolderId): DriveFile
    {
        $fileMetadata = new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentFolderId]
        ]);

        return $this->driveService->files->create($fileMetadata, [
            'fields' => 'id, name, webViewLink',
            'supportsAllDrives' => true,  // Adicione isso
        ]);
    }

    /**
     * Busca uma pasta pelo nome dentro de outra pasta
     */
    public function findFolder(string $name, string $parentFolderId): ?DriveFile
    {
        $query = sprintf(
            "name = '%s' and '%s' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            addslashes($name),
            $parentFolderId
        );

        $response = $this->driveService->files->listFiles([
            'q' => $query,
            'fields' => 'files(id, name, webViewLink)',
            'pageSize' => 1,
            'supportsAllDrives' => true,      // Adicione isso
            'includeItemsFromAllDrives' => true,  // E isso
        ]);

        $files = $response->getFiles();
        return count($files) > 0 ? $files[0] : null;
    }

    /**
     * Busca ou cria uma pasta
     */
    public function findOrCreateFolder(string $name, string $parentFolderId): DriveFile
    {
        $folder = $this->findFolder($name, $parentFolderId);

        if ($folder) {
            return $folder;
        }

        return $this->createFolder($name, $parentFolderId);
    }

    /**
     * Cria a estrutura: colaboradores/{empresa}/{nome}
     * Retorna a pasta do colaborador com a URL
     */
    public function createColaboradorFolder(string $empresa, string $nomeColaborador): array
    {
        $rootFolderId = env('GOOGLE_DRIVE_FOLDER_COLABORADORES_ID');

        // Busca ou cria pasta da empresa
        $empresaFolder = $this->findOrCreateFolder($empresa, $rootFolderId);

        // Busca ou cria pasta do colaborador
        $colaboradorFolder = $this->findOrCreateFolder($nomeColaborador, $empresaFolder->getId());

        return [
            'folder_id' => $colaboradorFolder->getId(),
            'folder_url' => $colaboradorFolder->getWebViewLink(),
            'folder_name' => $colaboradorFolder->getName(),
        ];
    }

    /**
     * Faz upload de um arquivo para uma pasta
     */
    public function uploadFile(string $filePath, string $fileName, string $folderId, string $mimeType = null): DriveFile
    {
        $fileMetadata = new DriveFile([
            'name' => $fileName,
            'parents' => [$folderId]
        ]);

        $content = file_get_contents($filePath);

        return $this->driveService->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $mimeType ?? mime_content_type($filePath),
            'uploadType' => 'multipart',
            'fields' => 'id, name, webViewLink',
            'supportsAllDrives' => true,  // Adicione isso
        ]);
    }
}
