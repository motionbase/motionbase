<?php

namespace App\Models\Traits;

use App\Models\Revision;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Revisionable
{
    protected bool $skipRevision = false;

    protected static function bootRevisionable(): void
    {
        // Create revision before updating
        static::updating(function ($model) {
            if (!$model->skipRevision) {
                $model->createRevision('update');
            }
        });

        // Create revision when created
        static::created(function ($model) {
            if (!$model->skipRevision) {
                $model->createRevision('create');
            }
        });

        // Create revision before deleting
        static::deleting(function ($model) {
            if (!$model->skipRevision) {
                $model->createRevision('delete');
            }
        });
    }

    public function revisions(): MorphMany
    {
        return $this->morphMany(Revision::class, 'revisionable')
            ->orderBy('created_at', 'desc');
    }

    protected function createRevision(string $type): void
    {
        // Don't create revision if no user is authenticated
        if (!auth()->check()) {
            return;
        }

        // Get the attributes that should be saved
        $content = $this->getRevisionableAttributes();

        // Create the revision
        $this->revisions()->create([
            'user_id' => auth()->id(),
            'content' => $content,
            'revision_type' => $type,
        ]);

        // Clean up old revisions (keep only 30)
        $this->pruneOldRevisions();
    }

    protected function getRevisionableAttributes(): array
    {
        // Get all attributes except timestamps
        $attributes = $this->getAttributes();

        // Use original values for 'update' to capture state before change
        if ($this->exists && $this->isDirty()) {
            $attributes = array_merge($attributes, $this->getOriginal());
        }

        // Remove attributes we don't want to track
        unset($attributes['updated_at']);

        return $attributes;
    }

    protected function pruneOldRevisions(): void
    {
        $maxRevisions = $this->getMaxRevisions();

        // Get all revision IDs ordered by created_at
        $allRevisionIds = $this->revisions()
            ->orderBy('created_at', 'desc')
            ->pluck('id');

        // Skip the first $maxRevisions and get the rest to delete
        $oldRevisions = $allRevisionIds->skip($maxRevisions);

        if ($oldRevisions->isNotEmpty()) {
            Revision::whereIn('id', $oldRevisions->toArray())->delete();
        }
    }

    protected function getMaxRevisions(): int
    {
        return property_exists($this, 'maxRevisions') ? $this->maxRevisions : 30;
    }

    public function restoreRevision(Revision $revision): bool
    {
        if ($revision->revisionable_id !== $this->id ||
            $revision->revisionable_type !== static::class) {
            return false;
        }

        // Update the model with the revision content
        $this->fill($revision->content);

        // Save without creating a new revision
        return $this->saveWithoutRevision();
    }

    public function saveWithoutRevision(): bool
    {
        $this->skipRevision = true;
        $result = $this->save();
        $this->skipRevision = false;

        return $result;
    }

    public function withoutRevision(callable $callback)
    {
        $this->skipRevision = true;
        $result = $callback($this);
        $this->skipRevision = false;

        return $result;
    }
}
