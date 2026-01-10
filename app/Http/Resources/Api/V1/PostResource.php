<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request)
    {
        $lang = $request->header('Accept-Language', 'uk');
        
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'title' => $this->name[$lang] ?? array_values($this->name)[0] ?? '',
            'description' => $this->description,
            'excerpt' => $this->description[$lang] ?? array_values($this->description)[0] ?? '',
            'content' => $this->content,
            'author' => $this->author,
            'author_name' => $this->author[$lang] ?? array_values($this->author)[0] ?? '',
            'image' => $this->image,
            'seo' => $this->seo,
            'visibility' => (bool)$this->visibility,
            'user' => new UserResource($this->whenLoaded('user')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}