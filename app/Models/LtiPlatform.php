<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LtiPlatform extends Model
{
    protected $fillable = [
        'name',
        'issuer',
        'client_id',
        'deployment_id',
        'auth_login_url',
        'auth_token_url',
        'jwks_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<LtiSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(LtiSession::class);
    }
}
