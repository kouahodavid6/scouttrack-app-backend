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
                'api_key' => env('CLOUDINARY_API_KEY'),
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
        $response = Http::withoutVerifying() // Désactive la vérification SSL
            ->timeout(30) // Timeout de 30 secondes
            ->asForm()
            ->post('https://api.imgbb.com/1/upload', [
                'key' => $apiKey,
                'image' => $imageContent,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            
            if (isset($data['data']['url'])) {
                return $data['data']['url'];
            } else {
                throw new \Exception("URL d'image non reçue de l'API ImgBB");
            }
        }
        
        throw new \Exception("Erreur upload image vers ImgBB");
    }
    
    /**
     * Upload video vers Cloudinary
     */
    public function uploadVideo($file)
    {
        try {
            $result = $this->cloudinary->uploadApi()->upload($file->getRealPath(), [
                'folder' => 'forum_videos',
                'resource_type' => 'video',
                'quality' => 'auto',
                'fetch_format' => 'auto',
            ]);
            
            return [
                'success' => true,
                'url' => $result['secure_url'],
                'public_id' => $result['public_id']
            ];
        } catch (\Exception $e) {
            Log::error('Cloudinary video error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Upload audio vers Cloudinary
     */
    public function uploadAudio($base64Audio)
    {
        try {
            // Décoder le base64
            $audioData = base64_decode($base64Audio);
            $filename = 'audio_' . Str::uuid() . '.webm';
            
            // Créer un fichier temporaire
            $tempPath = storage_path('app/temp/' . $filename);
            
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            file_put_contents($tempPath, $audioData);
            
            // Upload vers Cloudinary
            $result = $this->cloudinary->uploadApi()->upload($tempPath, [
                'folder' => 'forum_audios',
                'resource_type' => 'video',
                'format' => 'webm',
            ]);
            
            // Supprimer le fichier temporaire
            unlink($tempPath);
            
            return [
                'success' => true,
                'url' => $result['secure_url'],
                'public_id' => $result['public_id']
            ];
        } catch (\Exception $e) {
            Log::error('Cloudinary audio error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}