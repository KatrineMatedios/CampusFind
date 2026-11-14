<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClaimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'user_id' => $this->user_id,
            'identifiers' => $this->identifiers,
            'proof_photo_url' => $this->proof_photo_url,
            'pickup_location' => $this->pickup_location,
            'pickup_time' => $this->pickup_time,
            'qr_code' => $this->qr_code,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'item' => new ItemResource($this->whenLoaded('item')),
            'claimant' => new UserResource($this->whenLoaded('claimant')),
        ];
    }
}
