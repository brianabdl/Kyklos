<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PunchEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'occurredAt'  => $this->occurred_at->toISOString(),
            'eventType'   => $this->event_type,
            'method'      => $this->method,
            'isFlagged'   => $this->is_flagged,
            'flagReason'  => $this->flag_reason,
            'userName'    => $this->whenLoaded('user', fn() => $this->user->full_name),
            'description' => $this->buildDescription(),
        ];
    }

    private function buildDescription(): string
    {
        $type = str_replace('_', '-', $this->event_type);
        $site = $this->relationLoaded('session') && $this->session->relationLoaded('site')
            ? ' · ' . $this->session->site->name
            : '';
        return $type . $site;
    }
}
