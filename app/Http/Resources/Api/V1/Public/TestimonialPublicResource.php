<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\Resources\Json\JsonResource;

class TestimonialPublicResource extends JsonResource
{
    public function toArray($request)
    {
        $lang = $request->header('Accept-Language', 'uk');
        
        return [
            'id' => $this->id,
            'type' => $this->type,
            'description' => $this->description[$lang] ?? array_values($this->description)[0] ?? '',
            'text' => $this->text,
            'image' => $this->image,
            'image_url' => $this->image ? asset('storage/' . $this->image) : null,
            'video' => $this->video,
            'order' => $this->order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}