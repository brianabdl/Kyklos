<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PunchSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'site_id', 'shift_id', 'state',
        'clocked_in_at', 'clocked_out_at',
        'work_seconds', 'break_seconds', 'overtime_seconds',
        'clock_in_method', 'clock_out_method',
        'clock_in_lat', 'clock_in_lng',
        'notes', 'is_flagged',
    ];

    protected function casts(): array
    {
        return [
            'clocked_in_at'  => 'datetime',
            'clocked_out_at' => 'datetime',
            'is_flagged'     => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(BreakRecord::class, 'session_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(PunchEvent::class, 'session_id');
    }

    public function activeBreak(): ?BreakRecord
    {
        return $this->breaks()->whereNull('ended_at')->first();
    }
}
