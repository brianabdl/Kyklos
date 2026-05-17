<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'fullName'  => $this->full_name,
            'email'     => $this->email,
            'role'      => $this->role,
            'avatarUrl' => $this->avatar_url,
            'isActive'  => $this->is_active,
            'org'       => [
                'id'   => $this->organization->id,
                'name' => $this->organization->name,
            ],
        ];
    }
}
