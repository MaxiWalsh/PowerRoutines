<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageUploadService
{
    /**
     * Guarda una imagen en el disco público y devuelve la URL accesible.
     * Si existía una imagen anterior, la elimina.
     */
    public function store(UploadedFile $file, string $folder, ?string $previousPath = null): string
    {
        if ($previousPath) {
            $this->delete($previousPath);
        }

        $path = $file->store($folder, 'public');

        return Storage::disk('public')->url($path);
    }

    /**
     * Elimina una imagen dado su path relativo (ej: "avatars/abc.jpg")
     * o su URL completa.
     */
    public function delete(string $pathOrUrl): void
    {
        // Si es una URL completa, extraemos el path relativo al disco público
        $path = str_contains($pathOrUrl, '/storage/')
            ? ltrim(substr($pathOrUrl, strpos($pathOrUrl, '/storage/') + 9), '/')
            : ltrim($pathOrUrl, '/');

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Extrae el path relativo desde una URL de storage.
     */
    public function pathFromUrl(string $url): ?string
    {
        if (!str_contains($url, '/storage/')) {
            return null;
        }
        return ltrim(substr($url, strpos($url, '/storage/') + 9), '/');
    }
}
