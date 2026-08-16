<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use RuntimeException;

class CloudinaryService
{
    private Cloudinary $cloudinary;

    public function __construct()
    {
        $url = config('cloudinary.url');

        if (!$url) {
            throw new RuntimeException(
                'Cloudinary belum dikonfigurasi.'
            );
        }

        $configuration = new Configuration($url);

        $this->cloudinary = new Cloudinary(
            $configuration
        );
    }

    public function uploadImage(
        string $path,
        string $folder = 'schoolcanteen/products'
    ): array {
        $result = $this->cloudinary
            ->uploadApi()
            ->upload(
                $path,
                [
                    'folder' => $folder,
                    'resource_type' => 'image',
                ]
            );

        $url = $result['secure_url'] ?? null;
        $publicId = $result['public_id'] ?? null;

        if (!$url || !$publicId) {
            throw new RuntimeException(
                'Response upload Cloudinary tidak lengkap.'
            );
        }

        return [
            'url' => $url,
            'public_id' => $publicId,
        ];
    }

    public function deleteImage(
        ?string $publicId
    ): void {
        if (!$publicId) {
            return;
        }

        $this->cloudinary
            ->uploadApi()
            ->destroy(
                $publicId,
                [
                    'resource_type' => 'image',
                    'invalidate' => true,
                ]
            );
    }
}