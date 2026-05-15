<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class BoletaFileStorage
{
    public function __construct(
        #[Autowire('%boletas_upload_dir%')]
        private readonly string $uploadDir,
    ) {
    }

    public function storeOriginal(UploadedFile $file): string
    {
        return $this->store($file, 'originales');
    }

    public function storeComprobante(UploadedFile $file): string
    {
        return $this->store($file, 'comprobantes');
    }

    public function getOriginalPath(?string $filename): ?string
    {
        return $this->path('originales', $filename);
    }

    public function getComprobantePath(?string $filename): ?string
    {
        return $this->path('comprobantes', $filename);
    }

    public function deleteOriginal(?string $filename): void
    {
        $this->deleteFile($this->getOriginalPath($filename));
    }

    public function deleteComprobante(?string $filename): void
    {
        $this->deleteFile($this->getComprobantePath($filename));
    }

    private function store(UploadedFile $file, string $folder): string
    {
        $directory = $this->uploadDir . DIRECTORY_SEPARATOR . $folder;

        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new \RuntimeException(sprintf('No se pudo crear el directorio de subida "%s".', $directory));
        }

        $extension = $file->guessExtension() ?: 'bin';
        $safePrefix = preg_replace('/[^a-zA-Z0-9_-]+/', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'archivo';
        $filename = sprintf('%s_%s.%s', $safePrefix, bin2hex(random_bytes(8)), $extension);

        $file->move($directory, $filename);

        return $filename;
    }

    private function path(string $folder, ?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        return $this->uploadDir . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $filename;
    }

    private function deleteFile(?string $path): void
    {
        if ($path && is_file($path)) {
            @unlink($path);
        }
    }
}

