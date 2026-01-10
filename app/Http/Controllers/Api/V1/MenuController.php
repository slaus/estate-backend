<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MenuResource;
use App\Http\Resources\Api\V1\Public\MenuPublicResource;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $menus = Menu::query()
            ->when($request->user(), function ($query) {
                return $query;
            }, function ($query) {
                return $query->published();
            })
            ->orderBy('_lft')
            ->paginate($request->get('per_page', 15));

        return MenuResource::collection($menus);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|array',
            'name.uk' => 'required|string',
            'layout' => 'required|integer',
            'properties' => 'nullable|array',
            'parent_id' => 'nullable|exists:menus,id',
            'visibility' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $menu = Menu::create($request->all());

        return new MenuResource($menu);
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
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|array',
            'name.uk' => 'sometimes|string',
            'layout' => 'sometimes|integer',
            'properties' => 'nullable|array',
            'parent_id' => 'nullable|exists:menus,id',
            'visibility' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $menu->update($request->all());

        return new MenuResource($menu);
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();

        return response()->json(null, 204);
    }

    public function rebuild(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Menu::rebuildTree($request->items);
            
            return response()->json([
                'message' => 'Меню перебудовано успішно'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Помилка перебудови меню',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Публичные методы
    public function indexPublic(Request $request)
    {
        $menus = Menu::published()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('_lft')
            ->get();

        return MenuPublicResource::collection($menus);
    }
}