<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PostResource;
use App\Http\Resources\Api\V1\Public\PostPublicResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::query()
            ->when($request->user(), function ($query) {
                return $query;
            }, function ($query) {
                return $query->published();
            })
            ->with(['user', 'tags'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return PostResource::collection($posts);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|unique:posts|max:255',
            'name' => 'required|array',
            'name.uk' => 'required|string',
            'description' => 'nullable|array',
            'content' => 'required|array',
            'content.uk' => 'required|string',
            'author' => 'nullable|array',
            'image' => 'nullable|string|max:255',
            'visibility' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['user_id'] = $request->user()->id;

        $post = Post::create($data);

        return new PostResource($post->load(['user', 'tags']));
    }

    public function show(Request $request, Post $post)
    {
        if (!$request->user() && !$post->visibility) {
            return response()->json([
                'message' => 'Пост не знайдений'
            ], 404);
        }

        return new PostResource($post->load(['user', 'tags']));
    }

    public function update(Request $request, Post $post)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'sometimes|unique:posts,slug,' . $post->id . '|max:255',
            'name' => 'sometimes|array',
            'name.uk' => 'sometimes|string',
            'description' => 'nullable|array',
            'content' => 'sometimes|array',
            'content.uk' => 'sometimes|string',
            'author' => 'nullable|array',
            'image' => 'nullable|string|max:255',
            'visibility' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $post->update($request->all());

        return new PostResource($post->load(['user', 'tags']));
    }

    public function destroy(Post $post)
    {
        $post->delete();

        return response()->json(null, 204);
    }

    // Публичные методы для сайта
    public function indexPublic(Request $request)
    {
        $posts = Post::published()
            ->with(['user', 'tags'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return PostPublicResource::collection($posts);
    }

    public function showPublic(Request $request, $slug)
    {
        $post = Post::where('slug', $slug)
            ->published()
            ->with(['user', 'tags'])
            ->first();

        if (!$post) {
            return response()->json([
                'message' => 'Пост не знайдений'
            ], 404);
        }

        return new PostPublicResource($post);
    }
}