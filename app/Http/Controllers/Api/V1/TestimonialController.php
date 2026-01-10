<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TestimonialResource;
use App\Http\Resources\Api\V1\Public\TestimonialPublicResource;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TestimonialController extends Controller
{
    public function index(Request $request)
    {
        $testimonials = Testimonial::query()
            ->when($request->user(), function ($query) {
                return $query;
            }, function ($query) {
                return $query->published();
            })
            ->orderBy('order')
            ->paginate($request->get('per_page', 15));

        return TestimonialResource::collection($testimonials);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:text,image,video',
            'description' => 'nullable|array',
            'text' => 'nullable|string',
            'image' => 'nullable|string',
            'video' => 'nullable|string',
            'order' => 'integer',
            'visibility' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $testimonial = Testimonial::create($request->all());

        return new TestimonialResource($testimonial);
    }

    public function show(Request $request, Testimonial $testimonial)
    {
        if (!$request->user() && !$testimonial->visibility) {
            return response()->json([
                'message' => 'Відгук не знайдений'
            ], 404);
        }

        return new TestimonialResource($testimonial);
    }

    public function update(Request $request, Testimonial $testimonial)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:text,image,video',
            'description' => 'nullable|array',
            'text' => 'nullable|string',
            'image' => 'nullable|string',
            'video' => 'nullable|string',
            'order' => 'sometimes|integer',
            'visibility' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $testimonial->update($request->all());

        return new TestimonialResource($testimonial);
    }

    public function destroy(Testimonial $testimonial)
    {
        $testimonial->delete();

        return response()->json(null, 204);
    }

    // Публичные методы
    public function indexPublic(Request $request)
    {
        $testimonials = Testimonial::published()
            ->orderBy('order')
            ->get();

        return TestimonialPublicResource::collection($testimonials);
    }
}