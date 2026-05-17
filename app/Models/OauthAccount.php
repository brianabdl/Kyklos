<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthAccount extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'provider', 'provider_uid'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
