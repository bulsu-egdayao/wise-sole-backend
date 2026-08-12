<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class CloudinaryService
{
    /**
     * Uploads a file to Cloudinary and returns its permanent public URL.
     */
    public static function upload(UploadedFile $file, string $folder): string
    {
        $cloudName = env('CLOUDINARY_CLOUD_NAME');
        $apiKey = env('CLOUDINARY_API_KEY');
        $apiSecret = env('CLOUDINARY_API_SECRET');

        $timestamp = time();
        $paramsToSign = [
            'folder' => $folder,
            'timestamp' => $timestamp,
        ];
        ksort($paramsToSign);
        $paramString = collect($paramsToSign)
            ->map(fn($v, $k) => "$k=$v")
            ->implode('&');
        $signature = sha1($paramString . $apiSecret);

        $response = Http::attach(
            'file',
            file_get_contents($file->getRealPath()),
            $file->getClientOriginalName()
        )->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'folder' => $folder,
            'signature' => $signature,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Cloudinary upload failed: ' . $response->body());
        }

        return $response->json('secure_url');
    }

    /**
     * Deletes an image from Cloudinary given its full URL
     * (extracts the public_id automatically).
     */
    public static function deleteByUrl(?string $url): void
    {
        if (!$url) {
            return;
        }

        $publicId = self::extractPublicId($url);
        if (!$publicId) {
            return;
        }

        $cloudName = env('CLOUDINARY_CLOUD_NAME');
        $apiKey = env('CLOUDINARY_API_KEY');
        $apiSecret = env('CLOUDINARY_API_SECRET');

        $timestamp = time();
        $paramsToSign = ['public_id' => $publicId, 'timestamp' => $timestamp];
        ksort($paramsToSign);
        $paramString = collect($paramsToSign)->map(fn($v, $k) => "$k=$v")->implode('&');
        $signature = sha1($paramString . $apiSecret);

        Http::post("https://api.cloudinary.com/v1_1/{$cloudName}/image/destroy", [
            'public_id' => $publicId,
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);
    }

    private static function extractPublicId(string $url): ?string
    {
        if (!preg_match('#/upload/(?:v\d+/)?(.+)\.\w+(?:\?.*)?$#', $url, $matches)) {
            return null;
        }
        return $matches[1];
    }
}