<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'name' => $this->name,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'category_id' => $this->category_id,
            'location' => $this->location,
            'description' => $this->description,
            'photo_url' => $this->photo_url,
            'date_reported' => $this->date_reported?->format('Y-m-d'),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'reporter' => new UserResource($this->whenLoaded('reporter')),
            'claims_count' => $this->whenCounted('claims'),
        ];
    }
}
