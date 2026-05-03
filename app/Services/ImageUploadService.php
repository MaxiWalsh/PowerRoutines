<?php

namespace App\Services;

use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\UploadedFile;

class ImageUploadService
{
    private UploadApi $uploadApi;

    public function __construct()
    {
        $config = new Configuration([
            'cloud' => [
                'cloud_name' => config('services.cloudinary.cloud_name'),
                'api_key'    => config('services.cloudinary.api_key'),
                'api_secret' => config('services.cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => true,
            ],
        ]);

        $this->uploadApi = new UploadApi($config);
    }

    /**
     * Sube una imagen a Cloudinary y devuelve la URL segura.
     * Si existía una imagen anterior, la elimina primero.
     */
    public function store(UploadedFile $file, string $folder, ?string $previousPath = null): string
    {
        if ($previousPath) {
            $this->delete($previousPath);
        }

        $result = $this->uploadApi->upload($file->getRealPath(), [
            'folder' => $folder,
        ]);

        return $result['secure_url'];
    }

    /**
     * Elimina un asset de Cloudinary dado su URL o public_id.
     */
    public function delete(string $pathOrUrl): void
    {
        $publicId = $this->extractPublicId($pathOrUrl);

        if ($publicId === null) {
            return;
        }

        $this->uploadApi->destroy($publicId);
    }

    /**
     * Extrae el public_id de una URL de Cloudinary.
     *
     * URL format: https://res.cloudinary.com/{cloud}/image/upload/{version}/{folder/filename}.{ext}
     *             https://res.cloudinary.com/{cloud}/image/upload/{folder/filename}.{ext}
     */
    public function pathFromUrl(string $url): ?string
    {
        return $this->extractPublicId($url);
    }

    /**
     * Extracts the Cloudinary public_id (including folder, without extension) from a URL.
     * Returns the input unchanged if it does not look like a Cloudinary URL, treating it
     * as an already-resolved public_id.
     */
    private function extractPublicId(string $pathOrUrl): ?string
    {
        if (!str_contains($pathOrUrl, 'res.cloudinary.com')) {
            // Not a Cloudinary URL — treat as a bare public_id passed directly
            return $pathOrUrl ?: null;
        }

        // Match everything after /upload/ (and optional version segment v\d+/)
        if (!preg_match('#/upload/(?:v\d+/)?(.+)$#', $pathOrUrl, $matches)) {
            return null;
        }

        // Strip the file extension to get the public_id
        $withoutExtension = preg_replace('/\.[^.]+$/', '', $matches[1]);

        return $withoutExtension ?: null;
    }
}
