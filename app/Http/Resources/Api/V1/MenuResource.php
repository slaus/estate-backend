<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'layout' => $this->layout,
            'properties' => $this->properties,
            '_lft' => $this->_lft,
            '_rgt' => $this->_rgt,
            'parent_id' => $this->parent_id,
            'visibility' => (bool)$this->visibility,
            'children' => MenuResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}