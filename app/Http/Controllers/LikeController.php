<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Identifie le type d'utilisateur connecté.
     */
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

    public function togglePostLikePrivate(Request $request, $postId)
    {
        $user     = $request->user();
        $userType = $this->getUserType($user);

        $post = Post::where('context', 'private')->find($postId);
        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post non trouvé'], 404);
        }

        $existing = Like::where([
            'likeable_id'   => $postId,
            'likeable_type' => Post::class,
            'author_type'   => $userType,
            'author_id'     => $user->id,
        ])->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            Like::create([
                'likeable_id'   => $postId,
                'likeable_type' => Post::class,
                'author_type'   => $userType,
                'author_id'     => $user->id,
            ]);
            $liked = true;
        }

        $count = Like::where([
            'likeable_id'   => $postId,
            'likeable_type' => Post::class,
        ])->count();

        return response()->json(['success' => true, 'liked' => $liked, 'likes_count' => $count]);
    }

    public function toggleCommentLikePrivate(Request $request, $commentId)
    {
        $user     = $request->user();
        $userType = $this->getUserType($user);

        $comment = Comment::find($commentId);
        if (!$comment) {
            return response()->json(['success' => false, 'message' => 'Commentaire non trouvé'], 404);
        }

        $existing = Like::where([
            'likeable_id'   => $commentId,
            'likeable_type' => Comment::class,
            'author_type'   => $userType,
            'author_id'     => $user->id,
        ])->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            Like::create([
                'likeable_id'   => $commentId,
                'likeable_type' => Comment::class,
                'author_type'   => $userType,
                'author_id'     => $user->id,
            ]);
            $liked = true;
        }

        $count = Like::where([
            'likeable_id'   => $commentId,
            'likeable_type' => Comment::class,
        ])->count();

        return response()->json(['success' => true, 'liked' => $liked, 'likes_count' => $count]);
    }

    public function togglePostLikePublic(Request $request, $postId)
    {
        $post = Post::where('context', 'public')->find($postId);
        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post non trouvé'], 404);
        }

        $sessionId = md5($request->ip() . $request->userAgent());

        $existing = Like::where([
            'likeable_id'   => $postId,
            'likeable_type' => Post::class,
            'author_type'   => 'visitor',
            'session_id'    => $sessionId,
        ])->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            Like::create([
                'likeable_id'   => $postId,
                'likeable_type' => Post::class,
                'author_type'   => 'visitor',
                'session_id'    => $sessionId,
            ]);
            $liked = true;
        }

        $count = Like::where([
            'likeable_id'   => $postId,
            'likeable_type' => Post::class,
        ])->count();

        return response()->json(['success' => true, 'liked' => $liked, 'likes_count' => $count]);
    }
}