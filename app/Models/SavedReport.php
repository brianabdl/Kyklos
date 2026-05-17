<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedReport extends Model
{
    use HasUuids;

    protected $fillable = [
        'org_id', 'created_by', 'title', 'report_type',
        'date_range_start', 'date_range_end',
        'schedule_cron', 'last_run_at',
    ];

    protected function casts(): array
    {
        return [
            'date_range_start' => 'date',
            'date_range_end'   => 'date',
            'last_run_at'      => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
