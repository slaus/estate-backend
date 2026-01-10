<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\Resources\Json\JsonResource;

class MenuPublicResource extends JsonResource
{
    public function toArray($request)
    {
        $lang = $request->header('Accept-Language', 'uk');
        
        return [
            'id' => $this->id,
            'name' => $this->name[$lang] ?? array_values($this->name)[0] ?? '',
            'layout' => $this->layout,
            'properties' => $this->properties,
            'url' => $this->properties['url'] ?? '#',
            'children' => MenuPublicResource::collection($this->whenLoaded('children')),
        ];
    }
}