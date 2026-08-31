<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait HasUniqueSlug
{
    /**
     * The set of records this model's slug has to be unique within.
     *
     * @return Builder<static>
     */
    abstract protected function slugSiblings(): Builder;

    /**
     * Turn $source into a slug that is still free among {@see slugSiblings()}.
     * Appends -1, -2, … on collision, and ignores the model's own row so
     * re-saving an unchanged slug is a no-op instead of drifting to "-1".
     */
    public function uniqueSlug(string $source): string
    {
        $base = Str::slug($source);

        if ($base === '') {
            $base = Str::lower(class_basename($this));
        }

        $slug = $base;
        $counter = 1;

        while ($this->slugTaken($slug)) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function slugTaken(string $slug): bool
    {
        return $this->slugSiblings()
            ->where('slug', $slug)
            ->when($this->exists, fn (Builder $query) => $query->whereKeyNot($this->getKey()))
            ->exists();
    }
}
