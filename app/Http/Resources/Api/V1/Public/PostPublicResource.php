<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\Resources\Json\JsonResource;

class PostPublicResource extends JsonResource
{
    public function toArray($request)
    {
        $lang = $request->header('Accept-Language', 'uk');
        
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->name[$lang] ?? array_values($this->name)[0] ?? '',
            'excerpt' => $this->description[$lang] ?? array_values($this->description)[0] ?? '',
            'content' => $this->content[$lang] ?? array_values($this->content)[0] ?? '',
            'author' => $this->author[$lang] ?? array_values($this->author)[0] ?? '',
            'image' => $this->image,
            'image_url' => $this->image ? asset('storage/' . $this->image) : null,
            'meta' => [
                'title' => $this->seo['meta_title'][$lang] ?? $this->seo['meta_title']['uk'] ?? '',
                'description' => $this->seo['meta_description'][$lang] ?? $this->seo['meta_description']['uk'] ?? '',
                'keywords' => $this->seo['meta_keywords'][$lang] ?? $this->seo['meta_keywords']['uk'] ?? '',
            ],
            'user' => $this->whenLoaded('user', function () use ($lang) {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'avatar' => $this->user->avatar_url,
                ];
            }),
            'tags' => $this->whenLoaded('tags', function () use ($lang) {
                return $this->tags->map(function ($tag) use ($lang) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name[$lang] ?? array_values($tag->name)[0] ?? '',
                        'slug' => $tag->slug,
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}