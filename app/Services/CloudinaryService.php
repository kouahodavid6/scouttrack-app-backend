<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CloudinaryService
{
    protected $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => [
                'secure' => true
            ]
        ]);
    }

    /**
     * Upload image vers ImgBB
     */
    public function uploadImage($file)
    {
        $apiKey = 'd81ec57fb36de1981c2ae96a7a4c47f6';

        if (!$file->isValid()) {
            throw new \Exception("Fichier image non valide.");
        }

        $imageContent = base64_encode(file_get_contents($file->getRealPath()));

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withoutVerifying()
            ->timeout(30)
            ->asForm()
            ->post('https://api.imgbb.com/1/upload', [
                'key'   => $apiKey,
                'image' => $imageContent,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['data']['url'])) {
                return $data['data']['url'];
            }
            throw new \Exception("URL d'image non reçue de l'API ImgBB");
        }

        throw new \Exception("Erreur upload image vers ImgBB: " . $response->status());
    }

    /**
     * Upload video vers Cloudinary
     */
    public function uploadVideo($file)
    {
        try {
            $result = $this->cloudinary->uploadApi()->upload($file->getRealPath(), [
                'folder'        => 'forum_videos',
                'resource_type' => 'video',
                'quality'       => 'auto',
                'fetch_format'  => 'auto',
            ]);

            return [
                'success'   => true,
                'url'       => $result['secure_url'],
                'public_id' => $result['public_id']
            ];
        } catch (\Exception $e) {
            Log::error('Cloudinary video error: ' . $e->getMessage());
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Upload audio vers Cloudinary
     *
     * Accepte soit :
     *   - une string base64 pure (sans préfixe)
     *   - une data URI complète : "data:audio/webm;base64,AAAA..."
     */
    public function uploadAudio($base64Audio)
    {
        try {
            if (str_contains($base64Audio, ',')) {
                $base64Audio = explode(',', $base64Audio, 2)[1];
            }

            $base64Audio = preg_replace('/\s+/', '', $base64Audio);
            $audioData = base64_decode($base64Audio, true);

            if ($audioData === false || strlen($audioData) === 0) {
                throw new \Exception("Décodage base64 échoué");
            }

            Log::info('Audio size: ' . strlen($audioData) . ' bytes'); // Debug

            $filename = 'audio_' . Str::uuid() . '.webm';
            $tempDir  = storage_path('app/temp');
            $tempPath = $tempDir . '/' . $filename;

            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Vérifier que l'écriture a réussi
            $written = file_put_contents($tempPath, $audioData);
            if ($written === false) {
                throw new \Exception("Impossible d'écrire le fichier temporaire: " . $tempPath);
            }

            $result = $this->cloudinary->uploadApi()->upload($tempPath, [
                'folder'        => 'forum_audios',
                'resource_type' => 'video',
                'format'        => 'webm',
            ]);

            @unlink($tempPath);

            return ['success' => true, 'url' => $result['secure_url'], 'public_id' => $result['public_id']];

        } catch (\Exception $e) {
            Log::error('Cloudinary audio error: ' . $e->getMessage());
            if (isset($tempPath) && file_exists($tempPath)) @unlink($tempPath);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}