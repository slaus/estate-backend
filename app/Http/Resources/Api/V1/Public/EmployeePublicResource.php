<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeePublicResource extends JsonResource
{
    public function toArray($request)
    {
        $lang = $request->header('Accept-Language', 'uk');
        
        return [
            'id' => $this->id,
            'name' => $this->name[$lang] ?? array_values($this->name)[0] ?? '',
            'position' => $this->position,
            'description' => $this->description[$lang] ?? array_values($this->description)[0] ?? '',
            'details' => $this->details[$lang] ?? $this->details,
            'image' => $this->image,
            'image_url' => $this->image ? asset('storage/' . $this->image) : null,
            'order' => $this->order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}