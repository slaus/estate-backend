<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120', // 5MB
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            
            $fileName = Str::random(20) . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            $path = $file->storeAs('uploads/images', $fileName, 'public');
            
            $url = Storage::url($path);
            
            return response()->json([
                'success' => true,
                'url' => asset($url),
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Файл не був завантажений'
        ], 400);
    }
}