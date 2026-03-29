<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Comment;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ForumPublicController extends Controller
{
    protected $cloudinaryService;
    
    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }
    
    public function getPostsCommentsPublics()
    {
        try {
            $posts = Post::where('context', 'public')
                ->with(['comments' => function($query) {
                    $query->orderBy('created_at', 'asc');
                }])
                ->orderBy('created_at', 'desc')
                ->get();

            $posts->each(function($post) {
                $post->is_me = false;
                $post->comments->each(function($comment) {
                    $comment->is_me = false;
                });
            });

            return response()->json([
                'success' => true,
                'data' => $posts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addPostPublic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string',
            'photo' => 'nullable|image|max:5120',
            'video' => 'nullable|file|mimes:mp4,mov,avi|max:20480',
            'audio_data' => 'nullable|string',
            'author_name' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $hasAudio = $request->has('audio_data') && !empty($request->audio_data);
        $hasPhoto = $request->hasFile('photo');
        $hasVideo = $request->hasFile('video');
        $hasMessage = $request->filled('message');

        if (!$hasMessage && !$hasPhoto && !$hasVideo && !$hasAudio) {
            return response()->json([
                'success' => false,
                'message' => 'Au moins un champ (message, photo, vidéo ou audio) est requis'
            ], 422);
        }

        // Règle : L'audio doit être seul, sans message, photo ou vidéo
        if ($hasAudio && ($hasMessage || $hasPhoto || $hasVideo)) {
            return response()->json([
                'success' => false,
                'message' => 'L\'audio doit être envoyé seul sans message, photo ou vidéo'
            ], 422);
        }

        try {
            $data = [
                'author_type' => 'visitor',
                'author_id' => null,
                'author_name' => $request->author_name ?? 'Anonyme',
                'context' => 'public',
                'message' => $request->message
            ];

            if ($hasPhoto) {
                try {
                    $data['photo_url'] = $this->cloudinaryService->uploadImage($request->file('photo'));
                } catch (\Exception $e) {
                    throw new \Exception("Erreur upload image: " . $e->getMessage());
                }
            }

            if ($hasVideo) {
                $uploadResult = $this->cloudinaryService->uploadVideo($request->file('video'));
                if ($uploadResult['success']) {
                    $data['video_url'] = $uploadResult['url'];
                } else {
                    throw new \Exception("Erreur upload vidéo: " . $uploadResult['error']);
                }
            }

            if ($hasAudio) {
                $uploadResult = $this->cloudinaryService->uploadAudio($request->audio_data);
                if ($uploadResult['success']) {
                    $data['audio_url'] = $uploadResult['url'];
                    $data['message'] = null; // Pas de message quand audio seul
                } else {
                    throw new \Exception("Erreur upload audio: " . $uploadResult['error']);
                }
            }

            $post = Post::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Post créé avec succès',
                'data' => $post
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addCommentPublic(Request $request, $postId)
    {
        $post = Post::where('context', 'public')->find($postId);
        
        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string',
            'video' => 'nullable|file|mimes:mp4,mov,avi|max:20480',
            'audio_data' => 'nullable|string',
            'author_name' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $hasAudio = $request->has('audio_data') && !empty($request->audio_data);
        $hasVideo = $request->hasFile('video');
        $hasMessage = $request->filled('message');

        if (!$hasMessage && !$hasVideo && !$hasAudio) {
            return response()->json([
                'success' => false,
                'message' => 'Au moins un champ (message, vidéo ou audio) est requis'
            ], 422);
        }

        // Règle : L'audio doit être seul
        if ($hasAudio && ($hasMessage || $hasVideo)) {
            return response()->json([
                'success' => false,
                'message' => 'L\'audio doit être envoyé seul sans message ou vidéo'
            ], 422);
        }

        try {
            $data = [
                'post_id' => $postId,
                'author_type' => 'visitor',
                'author_id' => null,
                'author_name' => $request->author_name ?? 'Anonyme',
                'message' => $hasAudio ? null : $request->message
            ];

            if ($hasVideo) {
                $uploadResult = $this->cloudinaryService->uploadVideo($request->file('video'));
                if ($uploadResult['success']) {
                    $data['video_url'] = $uploadResult['url'];
                } else {
                    throw new \Exception("Erreur upload vidéo: " . $uploadResult['error']);
                }
            }

            if ($hasAudio) {
                $uploadResult = $this->cloudinaryService->uploadAudio($request->audio_data);
                if ($uploadResult['success']) {
                    $data['audio_url'] = $uploadResult['url'];
                } else {
                    throw new \Exception("Erreur upload audio: " . $uploadResult['error']);
                }
            }

            $comment = Comment::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Commentaire ajouté avec succès',
                'data' => $comment
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout du commentaire',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}