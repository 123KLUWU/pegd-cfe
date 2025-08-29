<?php

namespace App\Services;

use Google\Service\Drive as GoogleDrive;
use Illuminate\Support\Str;

class DriveExporter
{
    public function __construct(private GoogleDriveService $gds) {}

    public function downloadWithNativeFormat(string $fileId): array
    {
        // Usa el servicio ya configurado
        $service = $this->gds->getDriveService();

        $file = $service->files->get($fileId, ['fields' => 'id,name,mimeType']);
        $name = pathinfo($file->getName(), PATHINFO_FILENAME) ?: 'documento';
        $mime = $file->getMimeType();

        $exportMap = [
            'application/vnd.google-apps.document'      => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', '.docx'],
            'application/vnd.google-apps.spreadsheet'   => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', '.xlsx'],
            'application/vnd.google-apps.presentation'  => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', '.pptx'],
            'application/vnd.google-apps.drawing'       => ['image/png', '.png'],
        ];

        if (isset($exportMap[$mime])) {
            [$exportMime, $ext] = $exportMap[$mime];
            $resp = $service->files->export($fileId, $exportMime, ['alt' => 'media']);
            $filename = $name.$ext;
        } else {
            $resp = $service->files->get($fileId, ['alt' => 'media']);
            $filename = $file->getName(); // tal cual
        }

        $bytes   = $resp->getBody()->getContents();
        $tmpPath = storage_path('app/tmp/'.Str::uuid()."-{$filename}");
        if (!is_dir(dirname($tmpPath))) @mkdir(dirname($tmpPath), 0775, true);
        file_put_contents($tmpPath, $bytes);

        return [$tmpPath, $filename];
    }
}
