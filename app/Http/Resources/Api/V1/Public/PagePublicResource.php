<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\Resources\Json\JsonResource;

class PagePublicResource extends JsonResource
{
    public function toArray($request)
    {
        $lang = $request->header('Accept-Language', 'uk');
        
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->name[$lang] ?? array_values($this->name)[0] ?? '',
            'content' => $this->content[$lang] ?? array_values($this->content)[0] ?? '',
            'meta' => [
                'title' => $this->seo['meta_title'][$lang] ?? $this->seo['meta_title']['uk'] ?? '',
                'description' => $this->seo['meta_description'][$lang] ?? $this->seo['meta_description']['uk'] ?? '',
                'keywords' => $this->seo['meta_keywords'][$lang] ?? $this->seo['meta_keywords']['uk'] ?? '',
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}