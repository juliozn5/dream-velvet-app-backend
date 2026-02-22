<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Story;
use App\Models\ChatUnlock;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get IDs of models the user has unlocked (paid for)
        $unlockedModelIds = ChatUnlock::where('user_id', $user->id)
            ->pluck('model_id')
            ->toArray();

        // Include the user's own posts + posts from unlocked models (public posts only)
        $authorIds = array_merge([$user->id], $unlockedModelIds);

        $posts = Post::whereIn('user_id', $authorIds)
            ->where(function ($q) use ($user, $unlockedModelIds) {
                // Show public posts from anyone in the list
                $q->where('is_exclusive', false);
                // Show exclusive posts only from unlocked models
                if (!empty($unlockedModelIds)) {
                    $q->orWhere(function ($q2) use ($unlockedModelIds) {
                        $q2->where('is_exclusive', true)
                            ->whereIn('user_id', $unlockedModelIds);
                    });
                }
                // Always show own posts
                $q->orWhere('user_id', $user->id);
            })
            ->with('user:id,name,avatar')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'posts' => $posts,
        ]);
    }

    /**
     * Get stories for the feed from unlocked models
     * Groups stories by user for the story circles UI
     */
    public function feedStories(Request $request)
    {
        $user = $request->user();

        // Get IDs of models the user has unlocked
        $unlockedModelIds = ChatUnlock::where('user_id', $user->id)
            ->pluck('model_id')
            ->toArray();

        // Include own stories + stories from unlocked models
        $authorIds = array_merge([$user->id], $unlockedModelIds);

        // Get active (non-expired) stories from those users
        $stories = Story::whereIn('user_id', $authorIds)
            ->where('expires_at', '>', now())
            ->with('user:id,name,avatar')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group stories by user
        $grouped = $stories->groupBy('user_id')->map(function ($userStories, $userId) use ($user) {
            $storyUser = $userStories->first()->user;
            return [
                'user_id' => $userId,
                'user' => [
                    'id' => $storyUser->id,
                    'name' => $storyUser->name,
                    'avatar' => $storyUser->avatar,
                ],
                'is_own' => $userId == $user->id,
                'stories' => $userStories->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'media_url' => $s->media_url,
                        'media_type' => $s->media_type,
                        'is_exclusive' => $s->is_exclusive,
                        'created_at' => $s->created_at->toIso8601String(),
                        'user' => [
                            'id' => $s->user->id,
                            'name' => $s->user->name,
                            'avatar' => $s->user->avatar,
                        ],
                    ];
                })->values(),
            ];
        })->values();

        return response()->json($grouped);
    }
}
