<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'item' => new ItemResource($this->whenLoaded('item')),
            'reason' => $this->reason,
            'details' => $this->details,
            'status' => $this->status,
            'admin_note' => $this->admin_note,
            'reported_by' => $this->reported_by,
            'reporter' => new UserResource($this->whenLoaded('reporter')),
            'resolved_by' => $this->resolved_by,
            'resolver' => new UserResource($this->whenLoaded('resolver')),
            'resolved_at' => $this->resolved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
