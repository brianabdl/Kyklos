<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'scheduledStart' => $this->scheduled_start->toISOString(),
            'scheduledEnd'   => $this->scheduled_end->toISOString(),
            'label'          => $this->label,
            'site'           => [
                'id'   => $this->site->id,
                'name' => $this->site->name,
            ],
        ];
    }
}
