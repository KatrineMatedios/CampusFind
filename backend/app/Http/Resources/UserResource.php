<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'student_id' => $this->student_id,
            'email' => $this->email,
            'college_program' => $this->college_program,
            'contact_number' => $this->contact_number,
            'avatar_url' => $this->avatar_url,
            'role' => $this->role,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
