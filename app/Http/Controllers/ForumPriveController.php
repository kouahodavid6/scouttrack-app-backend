<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Comment;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ForumPriveController extends Controller
{
    protected $cloudinaryService;
    
    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }
    
    // Lister tous les posts privés
    public function getPostsCommentsPrivates(Request $request)
    {
        try {
            $user = $request->user();
            $userType = $user->getTable();
            
            $posts = Post::where('context', 'private')
                ->with(['comments' => function($query) use ($userType, $user) {
                    $query->orderBy('created_at', 'asc');
                }])
                ->orderBy('created_at', 'desc')
                ->get();

            $posts->each(function($post) use ($userType, $user) {
                $post->is_me = ($post->author_type === $userType && $post->author_id === $user->id);
                $post->comments->each(function($comment) use ($userType, $user) {
                    $comment->is_me = ($comment->author_type === $userType && $comment->author_id === $user->id);
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

    // Créer un post privé
    public function addPostPrivate(Request $request)
    {
        $user = $request->user();
        $userType = $user->getTable();

        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string',
            'photo' => 'nullable|image|max:5120',
            'video' => 'nullable|file|mimes:mp4,mov,avi|max:20480',
            'audio_data' => 'nullable|string',
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

        if ($hasAudio && ($hasMessage || $hasPhoto || $hasVideo)) {
            return response()->json([
                'success' => false,
                'message' => 'L\'audio doit être envoyé seul sans message, photo ou vidéo'
            ], 422);
        }

        try {
            $data = [
                'author_type' => $userType,
                'author_id' => $user->id,
                'author_name' => $user->nom ?? $user->name ?? $user->email,
                'context' => 'private',
                'message' => $request->message
            ];

            // Images : ImgBB
            if ($hasPhoto) {
                try {
                    $data['photo_url'] = $this->cloudinaryService->uploadImage($request->file('photo'));
                } catch (\Exception $e) {
                    throw new \Exception("Erreur upload image: " . $e->getMessage());
                }
            }

            // Vidéos : Cloudinary
            if ($hasVideo) {
                $uploadResult = $this->cloudinaryService->uploadVideo($request->file('video'));
                if ($uploadResult['success']) {
                    $data['video_url'] = $uploadResult['url'];
                } else {
                    throw new \Exception("Erreur upload vidéo: " . $uploadResult['error']);
                }
            }

            // Audio : Cloudinary
            if ($hasAudio) {
                $uploadResult = $this->cloudinaryService->uploadAudio($request->audio_data);
                if ($uploadResult['success']) {
                    $data['audio_url'] = $uploadResult['url'];
                } else {
                    throw new \Exception("Erreur upload audio: " . $uploadResult['error']);
                }
            }

            $post = Post::create($data);
            $post->is_me = true;

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

    // Modifier un post
    public function updatePostPrivate(Request $request, $id)
    {
        $user = $request->user();
        $userType = $user->getTable();
        
        $post = Post::where('context', 'private')->find($id);
        
        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post non trouvé'
            ], 404);
        }
        
        if (!$post->isAuthor($userType, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier ce post'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string',
            'photo' => 'nullable|image|max:5120',
            'video' => 'nullable|file|mimes:mp4,mov,avi|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($request->has('message')) {
                $post->message = $request->message;
            }
            
            // Images : ImgBB
            if ($request->hasFile('photo')) {
                try {
                    $post->photo_url = $this->cloudinaryService->uploadImage($request->file('photo'));
                } catch (\Exception $e) {
                    throw new \Exception("Erreur upload image: " . $e->getMessage());
                }
            }
            
            // Vidéos : Cloudinary
            if ($request->hasFile('video')) {
                $uploadResult = $this->cloudinaryService->uploadVideo($request->file('video'));
                if ($uploadResult['success']) {
                    $post->video_url = $uploadResult['url'];
                } else {
                    throw new \Exception("Erreur upload vidéo: " . $uploadResult['error']);
                }
            }
            
            $post->save();
            $post->is_me = true;

            return response()->json([
                'success' => true,
                'message' => 'Post modifié avec succès',
                'data' => $post
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Supprimer un post
    public function deletePostPrivate(Request $request, $id)
    {
        $user = $request->user();
        $userType = $user->getTable();
        
        $post = Post::where('context', 'private')->find($id);
        
        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post non trouvé'
            ], 404);
        }
        
        if (!$post->isAuthor($userType, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à supprimer ce post'
            ], 403);
        }

        try {
            $post->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Post supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Ajouter un commentaire
    public function addCommentPrivate(Request $request, $postId)
    {
        $user = $request->user();
        $userType = $user->getTable();
        
        $post = Post::where('context', 'private')->find($postId);
        
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

        try {
            $data = [
                'post_id' => $postId,
                'author_type' => $userType,
                'author_id' => $user->id,
                'author_name' => $user->nom ?? $user->name ?? $user->email,
                'message' => $request->message
            ];

            // Vidéos : Cloudinary
            if ($hasVideo) {
                $uploadResult = $this->cloudinaryService->uploadVideo($request->file('video'));
                if ($uploadResult['success']) {
                    $data['video_url'] = $uploadResult['url'];
                } else {
                    throw new \Exception("Erreur upload vidéo: " . $uploadResult['error']);
                }
            }

            // Audio : Cloudinary
            if ($hasAudio) {
                $uploadResult = $this->cloudinaryService->uploadAudio($request->audio_data);
                if ($uploadResult['success']) {
                    $data['audio_url'] = $uploadResult['url'];
                } else {
                    throw new \Exception("Erreur upload audio: " . $uploadResult['error']);
                }
            }

            $comment = Comment::create($data);
            $comment->is_me = true;

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

    // Modifier un commentaire
    public function updateCommentPrivate(Request $request, $id)
    {
        $user = $request->user();
        $userType = $user->getTable();
        
        $comment = Comment::find($id);
        
        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Commentaire non trouvé'
            ], 404);
        }
        
        if (!$comment->isAuthor($userType, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier ce commentaire'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($request->has('message')) {
                $comment->message = $request->message;
            }
            
            $comment->save();

            return response()->json([
                'success' => true,
                'message' => 'Commentaire modifié avec succès',
                'data' => $comment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du commentaire',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Supprimer un commentaire
    public function deleteCommentPrivate(Request $request, $id)
    {
        $user = $request->user();
        $userType = $user->getTable();
        
        $comment = Comment::find($id);
        
        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Commentaire non trouvé'
            ], 404);
        }
        
        if (!$comment->isAuthor($userType, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à supprimer ce commentaire'
            ], 403);
        }

        try {
            $comment->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Commentaire supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du commentaire',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}