<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MenuResource;
use App\Models\Menu;
use App\Models\Page; // Добавьте этот импорт
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Menu::withDepth()
                ->defaultOrder();

            if (!$request->user()) {
                $query->where('visibility', true);
            }

            $menus = $query->get();

            return MenuResource::collection($menus);
        } catch (\Exception $e) {
            Log::error('Error fetching menus: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching menus',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPages()
    {
        try {
            Log::info('Fetching pages for menu...');
            
            $pages = Page::select('id', 'name', 'slug', 'visibility')
                ->where('visibility', true)
                ->get()
                ->map(function ($page) {
                    return [
                        'id' => $page->id,
                        'name' => $page->name,
                        'slug' => $page->slug,
                    ];
                });

            Log::info('Pages fetched successfully:', ['count' => count($pages)]);

            return response()->json($pages);
        } catch (\Exception $e) {
            Log::error('Error fetching pages: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching pages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info('Menu store request received:', $request->all());

        $validator = Validator::make($request->all(), [
            'name' => 'required|array',
            'name.uk' => 'required|string',
            'name.en' => 'sometimes|string',
            'layout' => 'required|integer|in:0,1',
            'properties' => 'required|array',
            'properties.target' => 'required|array',
            'properties.target.type' => 'required|in:page,link',
            'properties.target.id' => 'required_if:properties.target.type,page|integer|nullable',
            'properties.target.name' => 'sometimes|array',
            'parent_id' => 'nullable|exists:menus,id',
            'visibility' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed:', $validator->errors()->toArray());
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();
            
            if (!isset($data['visibility'])) {
                $data['visibility'] = true;
            }
            
            Log::info('Creating menu with data:', $data);

            if (isset($data['parent_id']) && $data['parent_id']) {
                $parent = Menu::find($data['parent_id']);
                if ($parent) {
                    $menu = new Menu($data);
                    $menu->parent_id = $parent->id;
                    $menu->save();
                } else {
                    $menu = Menu::create($data);
                }
            } else {
                $menu = Menu::create($data);
            }

            DB::commit();
            
            Log::info('Menu created successfully:', ['id' => $menu->id]);

            return new MenuResource($menu->fresh());

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating menu: ' . $e->getMessage());
            return response()->json([
                'message' => 'Помилка створення меню',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, Menu $menu)
    {
        if (!$request->user() && !$menu->visibility) {
            return response()->json([
                'message' => 'Меню не знайдено'
            ], 404);
        }

        return new MenuResource($menu);
    }

    public function update(Request $request, Menu $menu)
    {
        Log::info('Menu update request:', ['id' => $menu->id, 'data' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|array',
            'name.uk' => 'sometimes|string',
            'name.en' => 'sometimes|string',
            'layout' => 'sometimes|integer|in:0,1',
            'properties' => 'sometimes|array',
            'properties.target' => 'sometimes|array',
            'properties.target.type' => 'sometimes|in:page,link',
            'properties.target.id' => 'required_if:properties.target.type,page|integer|nullable',
            'properties.target.name' => 'sometimes|array',
            'parent_id' => 'nullable|exists:menus,id',
            'visibility' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->except('parent_id');

            if ($request->has('parent_id') && $request->parent_id != $menu->parent_id) {
                if ($request->parent_id) {
                    $parent = Menu::find($request->parent_id);
                    if ($parent) {
                        $menu->appendToNode($parent)->save();
                    }
                } else {
                    $menu->saveAsRoot();
                }
            }

            $menu->update($data);

            DB::commit();

            return new MenuResource($menu->fresh());

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating menu: ' . $e->getMessage());
            return response()->json([
                'message' => 'Помилка оновлення меню',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Menu $menu)
    {
        try {
            $menu->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Error deleting menu: ' . $e->getMessage());
            return response()->json([
                'message' => 'Помилка видалення меню',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function rebuild(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'layout' => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            Menu::where('layout', $request->layout)->delete();

            $this->rebuildTree($request->items, null, $request->layout);

            DB::commit();

            return response()->json([
                'message' => 'Menu rebuilt successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rebuilding menu: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error rebuilding menu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function rebuildTree($items, $parentId = null, $layout)
    {
        foreach ($items as $item) {
            $menu = Menu::create([
                'layout' => $layout,
                'name' => $item['name'],
                'properties' => $item['properties'],
                'visibility' => $item['visibility'] ?? true,
                'parent_id' => $parentId
            ]);

            if (isset($item['children']) && is_array($item['children']) && count($item['children']) > 0) {
                $this->rebuildTree($item['children'], $menu->id, $layout);
            }
        }
    }
}