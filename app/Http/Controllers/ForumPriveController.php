<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ForumPriveController extends Controller
{
    protected $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    private function getUserType($user): string
    {
        $map = [
            \App\Models\Nation::class   => 'nation',
            \App\Models\Region::class   => 'region',
            \App\Models\District::class => 'district',
            \App\Models\Groupe::class   => 'groupe',
            \App\Models\CU::class       => 'cu',
            \App\Models\Jeune::class    => 'jeune',
        ];
        return $map[get_class($user)] ?? $user->getTable();
    }

    public function getPostsCommentsPrivates(Request $request)
    {
        try {
            $user     = $request->user();
            $userType = $this->getUserType($user);

            $posts = Post::where('context', 'private')
                ->with([
                    'comments' => fn($q) => $q->orderBy('created_at', 'asc'),
                    'likes',
                    'comments.likes',
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            $posts->each(function ($post) use ($userType, $user) {
                $post->is_me       = ($post->author_type === $userType && $post->author_id === $user->id);
                $post->likes_count = $post->likes->count();
                $post->is_liked    = $post->likes->contains(
                    fn($l) => $l->author_type === $userType && $l->author_id === $user->id
                );
                $post->unsetRelation('likes');

                $post->comments->each(function ($comment) use ($userType, $user) {
                    $comment->is_me       = ($comment->author_type === $userType && $comment->author_id === $user->id);
                    $comment->likes_count = $comment->likes->count();
                    $comment->is_liked    = $comment->likes->contains(
                        fn($l) => $l->author_type === $userType && $l->author_id === $user->id
                    );
                    $comment->unsetRelation('likes');
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

    public function addPostPrivate(Request $request)
    {
        $user     = $request->user();
        $userType = $this->getUserType($user);

        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string|max:5000',
            'photo'   => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors'  => $validator->errors()
            ], 422);
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
                'author_type' => $userType,
                'author_id'   => $user->id,
                'author_name' => $user->nom ?? $user->name ?? $user->email,
                'context'     => 'private',
                'message'     => $request->message,
            ];

            if ($hasPhoto) {
                $data['photo_url'] = $this->cloudinaryService->uploadImage($request->file('photo'));
            }

            $post              = Post::create($data);
            $post->is_me       = true;
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

    public function updatePostPrivate(Request $request, $id)
    {
        $user     = $request->user();
        $userType = $this->getUserType($user);

        $post = Post::where('context', 'private')->find($id);

        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post non trouvé'], 404);
        }

        if (!$post->isAuthor($userType, $user->id)) {
            return response()->json(['success' => false, 'message' => "Vous n'êtes pas autorisé à modifier ce post"], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string|max:5000',
            'photo'   => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            if ($request->has('message')) {
                $post->message = $request->message;
            }

            if ($request->hasFile('photo')) {
                $post->photo_url = $this->cloudinaryService->uploadImage($request->file('photo'));
            }

            $post->save();
            $post->is_me       = true;
            $post->likes_count = $post->likes()->count();
            $post->is_liked    = $post->likes()->where('author_type', $userType)->where('author_id', $user->id)->exists();

            return response()->json(['success' => true, 'message' => 'Post modifié avec succès', 'data' => $post]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du post',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function deletePostPrivate(Request $request, $id)
    {
        $user     = $request->user();
        $userType = $this->getUserType($user);

        $post = Post::where('context', 'private')->find($id);

        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post non trouvé'], 404);
        }

        if (!$post->isAuthor($userType, $user->id)) {
            return response()->json(['success' => false, 'message' => "Vous n'êtes pas autorisé à supprimer ce post"], 403);
        }

        try {
            // Supprimer aussi les likes liés
            Like::where('likeable_id', $post->id)->where('likeable_type', Post::class)->delete();
            $post->delete();
            return response()->json(['success' => true, 'message' => 'Post supprimé avec succès']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du post',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function addCommentPrivate(Request $request, $postId)
    {
        $user     = $request->user();
        $userType = $this->getUserType($user);

        $post = Post::where('context', 'private')->find($postId);

        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post non trouvé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $comment              = Comment::create([
                'post_id'     => $postId,
                'author_type' => $userType,
                'author_id'   => $user->id,
                'author_name' => $user->nom ?? $user->name ?? $user->email,
                'message'     => $request->message,
            ]);
            $comment->is_me       = true;
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

    public function updateCommentPrivate(Request $request, $id)
    {
        $user     = $request->user();
        $userType = $this->getUserType($user);

        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json(['success' => false, 'message' => 'Commentaire non trouvé'], 404);
        }

        if (!$comment->isAuthor($userType, $user->id)) {
            return response()->json(['success' => false, 'message' => "Vous n'êtes pas autorisé à modifier ce commentaire"], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $comment->message     = $request->message;
            $comment->save();
            $comment->likes_count = $comment->likes()->count();
            $comment->is_liked    = $comment->likes()->where('author_type', $userType)->where('author_id', $user->id)->exists();

            return response()->json(['success' => true, 'message' => 'Commentaire modifié avec succès', 'data' => $comment]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du commentaire',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function deleteCommentPrivate(Request $request, $id)
    {
        $user     = $request->user();
        $userType = $this->getUserType($user);

        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json(['success' => false, 'message' => 'Commentaire non trouvé'], 404);
        }

        if (!$comment->isAuthor($userType, $user->id)) {
            return response()->json(['success' => false, 'message' => "Vous n'êtes pas autorisé à supprimer ce commentaire"], 403);
        }

        try {
            Like::where('likeable_id', $comment->id)->where('likeable_type', Comment::class)->delete();
            $comment->delete();
            return response()->json(['success' => true, 'message' => 'Commentaire supprimé avec succès']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du commentaire',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}