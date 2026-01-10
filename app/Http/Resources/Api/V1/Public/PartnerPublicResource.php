<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\Resources\Json\JsonResource;

class PartnerPublicResource extends JsonResource
{
    public function toArray($request)
    {
        $lang = $request->header('Accept-Language', 'uk');
        
        return [
            'id' => $this->id,
            'description' => $this->description[$lang] ?? array_values($this->description)[0] ?? '',
            'image' => $this->image,
            'image_url' => $this->image ? asset('storage/' . $this->image) : null,
            'order' => $this->order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}