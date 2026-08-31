<?php

namespace App\Mcp\Concerns;

use App\Models\Chapter;
use App\Models\Section;
use App\Models\Topic;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Every lookup goes through the acting user's own topics.
 *
 * The admin UI scopes the same way (TopicController::index), and an MCP token
 * must not be a way around that: a missing record and someone else's record
 * both come back as null, so the tools cannot be used to probe for ids.
 */
trait ResolvesOwnedContent
{
    protected function findTopic(?Authenticatable $user, int $topicId): ?Topic
    {
        if (! $user) {
            return null;
        }

        return Topic::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->find($topicId);
    }

    protected function findChapter(?Authenticatable $user, int $chapterId): ?Chapter
    {
        if (! $user) {
            return null;
        }

        return Chapter::query()
            ->whereHas('topic', fn ($query) => $query->where('user_id', $user->getAuthIdentifier()))
            ->find($chapterId);
    }

    protected function findSection(?Authenticatable $user, int $sectionId): ?Section
    {
        if (! $user) {
            return null;
        }

        return Section::query()
            ->whereHas('chapter.topic', fn ($query) => $query->where('user_id', $user->getAuthIdentifier()))
            ->find($sectionId);
    }
}
