<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'timezone'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'org_id');
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class, 'org_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'org_id');
    }

    public function savedReports(): HasMany
    {
        return $this->hasMany(SavedReport::class, 'org_id');
    }
}
