<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    public function toArray($request)
    {
        $lang = $request->header('Accept-Language', 'uk');
        
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'title' => $this->name[$lang] ?? array_values($this->name)[0] ?? '',
            'content' => $this->content,
            'seo' => $this->seo,
            'visibility' => (bool)$this->visibility,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}