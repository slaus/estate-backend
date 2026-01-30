<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PageResource;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $pages = Page::query()
            ->when($request->user(), function ($query) {
                return $query;
            }, function ($query) {
                return $query->published();
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return PageResource::collection($pages);
    }

    public function list()
    {
        $pages = Page::select('id', 'name', 'slug', 'visibility')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($pages);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|unique:pages|max:255',
            'name' => 'required|array',
            'name.uk' => 'required|string',
            'content' => 'required|array',
            'content.uk' => 'required|string',
            'seo' => 'sometimes|array',
            'seo.meta_title' => 'sometimes|array',
            'seo.meta_description' => 'sometimes|array',
            'seo.meta_keywords' => 'sometimes|array',
            'visibility' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        
        if (!isset($data['seo'])) {
            $data['seo'] = $this->generateSeo($data);
        }

        $page = Page::create($data);

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
            'seo' => 'sometimes|array',
            'seo.meta_title' => 'sometimes|array',
            'seo.meta_description' => 'sometimes|array',
            'seo.meta_keywords' => 'sometimes|array',
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

    public function generateSeo(Request $request, $id)
    {
        $page = Page::findOrFail($id);
        
        $seo = $this->generateSeoData([
            'name' => $page->name,
            'content' => $page->content,
        ]);
        
        $page->update(['seo' => $seo]);

        return response()->json([
            'message' => 'SEO згенеровано успішно',
            'seo' => $seo
        ]);
    }

    private function generateSeoData($data)
    {
        $seo = [
            'meta_title' => [],
            'meta_description' => [],
            'meta_keywords' => []
        ];

        foreach (['uk', 'en'] as $lang) {
            if (isset($data['name'][$lang])) {
                $seo['meta_title'][$lang] = $data['name'][$lang];
                
                if (isset($data['content'][$lang])) {
                    $content = strip_tags($data['content'][$lang]);
                    $seo['meta_description'][$lang] = Str::limit($content, 160);
                    
                    $keywords = explode(' ', $data['name'][$lang]);
                    $keywords = array_slice($keywords, 0, 5);
                    $seo['meta_keywords'][$lang] = implode(', ', $keywords);
                }
            }
        }

        return $seo;
    }
}