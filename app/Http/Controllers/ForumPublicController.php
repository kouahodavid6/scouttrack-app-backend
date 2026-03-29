<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
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

    public function getPostsCommentsPublics(Request $request)
    {
        try {
            $sessionId = md5($request->ip() . $request->userAgent());

            $posts = Post::where('context', 'public')
                ->with([
                    'comments' => fn($q) => $q->orderBy('created_at', 'asc'),
                    'likes',
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            $posts->each(function ($post) use ($sessionId) {
                $post->is_me       = false;
                $post->likes_count = $post->likes->count();
                $post->is_liked    = $post->likes->contains(
                    fn($l) => $l->session_id === $sessionId
                );
                $post->unsetRelation('likes');

                $post->comments->each(function ($comment) {
                    $comment->is_me       = false;
                    $comment->likes_count = 0;
                    $comment->is_liked    = false;
                });
            });

            return response()->json(['success' => true, 'data' => $posts]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des posts',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function addPostPublic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message'     => 'nullable|string|max:5000',
            'photo'       => 'nullable|image|max:5120',
            'author_name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $hasPhoto   = $request->hasFile('photo');
        $hasMessage = $request->filled('message');

        if (!$hasMessage && !$hasPhoto) {
            return response()->json(['success' => false, 'message' => 'Au moins un contenu est requis'], 422);
        }

        if ($hasPhoto && !$hasMessage) {
            return response()->json(['success' => false, 'message' => "Une photo doit être accompagnée d'un message"], 422);
        }

        try {
            $data = [
                'author_type' => 'visitor',
                'author_id'   => null,
                'author_name' => $request->author_name ?? 'Anonyme',
                'context'     => 'public',
                'message'     => $request->message,
            ];

            if ($hasPhoto) {
                $data['photo_url'] = $this->cloudinaryService->uploadImage($request->file('photo'));
            }

            $post              = Post::create($data);
            $post->likes_count = 0;
            $post->is_liked    = false;

            return response()->json(['success' => true, 'message' => 'Post créé avec succès', 'data' => $post], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du post',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function addCommentPublic(Request $request, $postId)
    {
        $post = Post::where('context', 'public')->find($postId);

        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post non trouvé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'message'     => 'required|string|max:5000',
            'author_name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $comment              = Comment::create([
                'post_id'     => $postId,
                'author_type' => 'visitor',
                'author_id'   => null,
                'author_name' => $request->author_name ?? 'Anonyme',
                'message'     => $request->message,
            ]);
            $comment->likes_count = 0;
            $comment->is_liked    = false;

            return response()->json(['success' => true, 'message' => 'Commentaire ajouté avec succès', 'data' => $comment], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Erreur lors de l'ajout du commentaire",
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}