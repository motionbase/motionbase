<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Section extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'chapter_id',
        'title',
        'content',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'content' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * @return BelongsTo<Chapter, $this>
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * Get the topic through the chapter.
     *
     * @return HasOneThrough<Topic, Chapter, $this>
     */
    public function topic(): HasOneThrough
    {
        return $this->hasOneThrough(
            Topic::class,
            Chapter::class,
            'id',           // Foreign key on chapters table
            'id',           // Foreign key on topics table
            'chapter_id',   // Local key on sections table
            'topic_id'      // Local key on chapters table
        );
    }
}
