<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LtiResourceLink extends Model
{
    protected $fillable = [
        'lti_platform_id',
        'resource_link_id',
        'content_type',
        'topic_id',
        'chapter_id',
        'section_id',
    ];
}
