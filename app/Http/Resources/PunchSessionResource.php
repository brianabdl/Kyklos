<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PunchSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $elapsedSeconds = $this->clocked_in_at
            ? now()->diffInSeconds($this->clocked_in_at)
            : null;

        $activeBreak = $this->relationLoaded('breaks')
            ? $this->breaks->whereNull('ended_at')->first()
            : null;

        return [
            'id'                   => $this->id,
            'state'                => $this->state,
            'clockedInAt'          => $this->clocked_in_at?->toISOString(),
            'clockedOutAt'         => $this->clocked_out_at?->toISOString(),
            'elapsedSeconds'       => $this->state !== 'clocked_out' ? $elapsedSeconds : null,
            'breakSeconds'         => $this->break_seconds ?? 0,
            'workSeconds'          => $this->work_seconds,
            'overtimeSeconds'      => $this->overtime_seconds,
            'isFlagged'            => $this->is_flagged,
            'currentBreakStartedAt'=> $activeBreak?->started_at?->toISOString(),
            'site'                 => $this->whenLoaded('site', fn() => [
                'id'   => $this->site->id,
                'name' => $this->site->name,
            ]),
            'shift'                => $this->whenLoaded('shift', fn() => $this->shift ? [
                'id'           => $this->shift->id,
                'scheduledEnd' => $this->shift->scheduled_end->toISOString(),
            ] : null),
        ];
    }
}
