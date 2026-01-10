<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PageResource;
use App\Http\Resources\Api\V1\Public\PagePublicResource;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $pages = Page::query()
            ->when($request->user(), function ($query) {
                // Админы видят все, публичные только опубликованные
                return $query;
            }, function ($query) {
                return $query->published();
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return PageResource::collection($pages);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|unique:pages|max:255',
            'name' => 'required|array',
            'name.uk' => 'required|string',
            'content' => 'required|array',
            'content.uk' => 'required|string',
            'visibility' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $page = Page::create($request->all());

        return new PageResource($page);
    }

    public function show(Request $request, Page $page)
    {
        if (!$request->user() && !$page->visibility) {
            return response()->json([
                'message' => 'Сторінка не знайдена'
            ], 404);
        }

        return new PageResource($page);
    }

    public function update(Request $request, Page $page)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'sometimes|unique:pages,slug,' . $page->id . '|max:255',
            'name' => 'sometimes|array',
            'name.uk' => 'sometimes|string',
            'content' => 'sometimes|array',
            'content.uk' => 'sometimes|string',
            'visibility' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $page->update($request->all());

        return new PageResource($page);
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return response()->json(null, 204);
    }

    // Публичные методы для сайта
    public function indexPublic(Request $request)
    {
        $pages = Page::published()
            ->orderBy('created_at', 'desc')
            ->get();

        return PagePublicResource::collection($pages);
    }

    public function showPublic(Request $request, $slug)
    {
        $page = Page::where('slug', $slug)
            ->published()
            ->first();

        if (!$page) {
            return response()->json([
                'message' => 'Сторінка не знайдена'
            ], 404);
        }

        return new PagePublicResource($page);
    }
}