<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PunchEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'session_id', 'user_id', 'event_type', 'occurred_at',
        'method', 'lat', 'lng', 'photo_url',
        'actor_id', 'is_flagged', 'flag_reason',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'is_flagged'  => 'boolean',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PunchSession::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
