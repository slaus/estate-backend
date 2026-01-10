<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PartnerResource;
use App\Http\Resources\Api\V1\Public\PartnerPublicResource;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $partners = Partner::query()
            ->when($request->user(), function ($query) {
                return $query;
            }, function ($query) {
                return $query->published();
            })
            ->orderBy('order')
            ->paginate($request->get('per_page', 15));

        return PartnerResource::collection($partners);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'nullable|array',
            'image' => 'nullable|string|max:255',
            'order' => 'integer',
            'visibility' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $partner = Partner::create($request->all());

        return new PartnerResource($partner);
    }

    public function show(Request $request, Partner $partner)
    {
        if (!$request->user() && !$partner->visibility) {
            return response()->json([
                'message' => 'Партнер не знайдений'
            ], 404);
        }

        return new PartnerResource($partner);
    }

    public function update(Request $request, Partner $partner)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'nullable|array',
            'image' => 'nullable|string|max:255',
            'order' => 'sometimes|integer',
            'visibility' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $partner->update($request->all());

        return new PartnerResource($partner);
    }

    public function destroy(Partner $partner)
    {
        $partner->delete();

        return response()->json(null, 204);
    }

    // Публичные методы
    public function indexPublic(Request $request)
    {
        $partners = Partner::published()
            ->orderBy('order')
            ->get();

        return PartnerPublicResource::collection($partners);
    }
}