<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LtiSession extends Model
{
    protected $fillable = [
        'lti_platform_id',
        'lti_user_id',
        'context_id',
        'resource_link_id',
        'claims',
        'session_token',
        'expires_at',
    ];

    protected $casts = [
        'claims' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<LtiPlatform, $this>
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(LtiPlatform::class, 'lti_platform_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
