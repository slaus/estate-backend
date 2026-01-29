<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'layout' => $this->layout,
            'properties' => $this->properties,
            'visibility' => (bool)$this->visibility,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Для админки добавляем дополнительные поля
        if ($request->user()) {
            $data['_lft'] = $this->_lft;
            $data['_rgt'] = $this->_rgt;
            $data['parent_id'] = $this->parent_id;
            $data['depth'] = $this->depth;
            $data['children'] = MenuResource::collection($this->whenLoaded('children'));
        }

        return $data;
    }
}