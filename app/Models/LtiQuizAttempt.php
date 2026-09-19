<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LtiQuizAttempt extends Model
{
    protected $fillable = [
        'lti_platform_id',
        'lti_user_id',
        'resource_link_id',
        'section_id',
        'block_id',
        'score',
        'max_score',
        'answers',
    ];

    protected $casts = [
        'answers' => 'array',
        'score' => 'integer',
        'max_score' => 'integer',
    ];
}
