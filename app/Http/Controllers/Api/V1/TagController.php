<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TagCollection;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $tags = Tag::orderBy('order_column')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
            
        return new TagCollection($tags);
    }
}