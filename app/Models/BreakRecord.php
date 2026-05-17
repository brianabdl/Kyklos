<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreakRecord extends Model
{
    use HasUuids;

    protected $table = 'break_records';

    protected $fillable = ['session_id', 'started_at', 'ended_at', 'duration_seconds'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at'   => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PunchSession::class, 'session_id');
    }
}
