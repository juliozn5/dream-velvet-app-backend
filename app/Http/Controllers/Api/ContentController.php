<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Story;
use App\Models\Highlight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContentController extends Controller
{
    /**
     * Crear un Post o Reel
     */
    public function storePost(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,webm|max:51200',
            'type' => 'required|in:post,reel',
            'caption' => 'nullable|string|max:2200',
            'is_exclusive' => 'required|boolean',
            'coin_cost' => 'nullable|integer|min:0',
        ]);

        $user = $request->user();
        $file = $request->file('file');

        $mime = $file->getMimeType();
        $mediaType = str_contains($mime, 'video') ? 'video' : 'image';

        // Reels must be video
        if ($request->type === 'reel' && $mediaType !== 'video') {
            return response()->json(['error' => 'Los reels deben ser videos.'], 422);
        }

        $path = $file->store('posts', 'public');
        $url = asset('storage/' . $path);

        $post = Post::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'media_url' => $url,
            'media_type' => $mediaType,
            'caption' => $request->caption,
            'is_exclusive' => $request->is_exclusive,
            'coin_cost' => $request->is_exclusive ? ($request->coin_cost ?? 5) : 0,
        ]);

        // Update user post count
        $user->increment('posts_count');

        return response()->json($post, 201);
    }

    /**
     * Crear una Historia
     */
    public function storeStory(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,webm|max:51200',
            'is_exclusive' => 'required|boolean',
        ]);

        $user = $request->user();
        $file = $request->file('file');

        $mime = $file->getMimeType();
        $mediaType = str_contains($mime, 'video') ? 'video' : 'image';

        $path = $file->store('stories', 'public');
        $url = asset('storage/' . $path);

        $story = Story::create([
            'user_id' => $user->id,
            'media_url' => $url,
            'media_type' => $mediaType,
            'is_exclusive' => $request->is_exclusive,
            'expires_at' => now()->addHours(24),
        ]);

        return response()->json($story, 201);
    }

    /**
     * Crear una Historia Destacada
     */
    public function storeHighlight(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:50',
            'cover' => 'nullable|file|mimes:jpeg,png,jpg|max:10240',
            'is_exclusive' => 'required|boolean',
        ]);

        $user = $request->user();
        $coverUrl = null;

        if ($request->hasFile('cover')) {
            $path = $request->file('cover')->store('highlights', 'public');
            $coverUrl = asset('storage/' . $path);
        }

        $highlight = Highlight::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'cover_url' => $coverUrl,
            'is_exclusive' => $request->is_exclusive,
        ]);

        return response()->json($highlight, 201);
    }

    /**
     * Obtener posts del usuario autenticado
     */
    public function myPosts(Request $request)
    {
        $type = $request->query('type', 'post'); // post or reel
        $exclusive = $request->query('exclusive'); // null, 0, 1

        $query = Post::where('user_id', $request->user()->id)
            ->where('type', $type);

        if ($exclusive !== null) {
            $query->where('is_exclusive', (bool) $exclusive);
        }

        $posts = $query->orderBy('created_at', 'desc')->get();

        return response()->json($posts);
    }

    /**
     * Obtener stories del usuario autenticado
     */
    public function myStories(Request $request)
    {
        $stories = Story::where('user_id', $request->user()->id)
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($stories);
    }

    /**
     * Obtener highlights del usuario autenticado
     */
    public function myHighlights(Request $request)
    {
        $highlights = Highlight::where('user_id', $request->user()->id)
            ->with('stories')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($highlights);
    }

    /**
     * Obtener posts de un usuario específico (perfil público)
     */
    public function userPosts(Request $request, $userId)
    {
        $type = $request->query('type', 'post');
        $exclusive = $request->query('exclusive');

        $query = Post::where('user_id', $userId)
            ->where('type', $type);

        if ($exclusive !== null) {
            $query->where('is_exclusive', (bool) $exclusive);
        }

        // TODO: validar si el usuario que solicita tiene acceso al contenido exclusivo
        $posts = $query->orderBy('created_at', 'desc')->get();

        return response()->json($posts);
    }

    /**
     * Eliminar un post
     */
    public function destroyPost(Request $request, $id)
    {
        $post = Post::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        // Delete file from storage
        $storagePath = str_replace(asset('storage/'), '', $post->media_url);
        Storage::disk('public')->delete($storagePath);

        $post->delete();
        $request->user()->decrement('posts_count');

        return response()->json(['message' => 'Publicación eliminada']);
    }

    /**
     * Eliminar una story
     */
    public function destroyStory(Request $request, $id)
    {
        $story = Story::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $storagePath = str_replace(asset('storage/'), '', $story->media_url);
        Storage::disk('public')->delete($storagePath);

        $story->delete();

        return response()->json(['message' => 'Historia eliminada']);
    }
}
