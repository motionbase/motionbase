<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Revision;
use App\Models\Section;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RevisionController extends Controller
{
    /**
     * Get all revisions for a model
     */
    public function index(Request $request, string $modelType, int $modelId): JsonResponse
    {
        $model = $this->resolveModel($modelType, $modelId);

        if (!$model) {
            return response()->json(['error' => 'Model not found'], 404);
        }

        // Check authorization
        $this->authorize('view', $model);

        $revisions = $model->revisions()
            ->with('user:id,name,email')
            ->get()
            ->map(function ($revision) {
                return [
                    'id' => $revision->id,
                    'user' => $revision->user,
                    'revision_type' => $revision->revision_type,
                    'created_at' => $revision->created_at->toISOString(),
                    'created_at_human' => $revision->created_at->diffForHumans(),
                ];
            });

        return response()->json($revisions);
    }

    /**
     * Get all revisions for a topic (including chapters and sections)
     */
    public function topicHistory(int $topicId): JsonResponse
    {
        $topic = Topic::with(['chapters.sections'])->findOrFail($topicId);

        // Check authorization
        $this->authorize('view', $topic);

        // Get all revision IDs for topic, chapters, and sections
        $topicRevisions = $topic->revisions()->pluck('id');

        $chapterRevisions = Revision::where('revisionable_type', Chapter::class)
            ->whereIn('revisionable_id', $topic->chapters->pluck('id'))
            ->pluck('id');

        $sectionRevisions = Revision::where('revisionable_type', Section::class)
            ->whereIn('revisionable_id', $topic->chapters->flatMap->sections->pluck('id'))
            ->pluck('id');

        // Combine all revision IDs
        $allRevisionIds = $topicRevisions
            ->concat($chapterRevisions)
            ->concat($sectionRevisions);

        // Get all revisions with their relationships
        $revisions = Revision::with(['user:id,name,email', 'revisionable'])
            ->whereIn('id', $allRevisionIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($revision) {
                $modelType = class_basename($revision->revisionable_type);

                // Get title from revision content, handle empty strings
                $title = $revision->content['title'] ?? '';
                if (empty(trim($title))) {
                    $title = 'Ohne Titel';
                }

                return [
                    'id' => $revision->id,
                    'user' => $revision->user,
                    'revision_type' => $revision->revision_type,
                    'model_type' => $modelType,
                    'model_id' => $revision->revisionable_id,
                    'title' => $title,
                    'created_at' => $revision->created_at->toISOString(),
                    'created_at_human' => $revision->created_at->diffForHumans(),
                ];
            });

        return response()->json($revisions);
    }

    /**
     * Get a specific revision with diff compared to previous revision
     */
    public function show(int $revisionId): JsonResponse
    {
        $revision = Revision::with(['user:id,name,email', 'revisionable'])->findOrFail($revisionId);

        // Check authorization
        $this->authorize('view', $revision->revisionable);

        // Get the previous revision (chronologically before this one)
        $previousRevision = Revision::where('revisionable_type', $revision->revisionable_type)
            ->where('revisionable_id', $revision->revisionable_id)
            ->where('created_at', '<', $revision->created_at)
            ->orderBy('created_at', 'desc')
            ->first();

        // Calculate diff between previous revision and this revision
        $diff = [];
        if ($previousRevision) {
            $diff = $this->calculateDiff($previousRevision->content, $revision->content);
        }

        // Get title from revision content, handle empty strings
        $title = $revision->content['title'] ?? '';
        if (empty(trim($title))) {
            $title = 'Ohne Titel';
        }

        return response()->json([
            'id' => $revision->id,
            'user' => $revision->user,
            'revision_type' => $revision->revision_type,
            'created_at' => $revision->created_at->toISOString(),
            'created_at_human' => $revision->created_at->diffForHumans(),
            'content' => $revision->content,
            'diff' => $diff,
            'has_previous' => $previousRevision !== null,
            'title' => $title,
            'model_type' => class_basename($revision->revisionable_type),
        ]);
    }

    /**
     * Restore a revision
     */
    public function restore(int $revisionId): JsonResponse
    {
        $revision = Revision::with('revisionable')->findOrFail($revisionId);

        // Check authorization
        $this->authorize('update', $revision->revisionable);

        $model = $revision->revisionable;
        $success = $model->restoreRevision($revision);

        if ($success) {
            return response()->json([
                'message' => 'Revision restored successfully',
                'model' => $model->fresh(),
            ]);
        }

        return response()->json(['error' => 'Failed to restore revision'], 500);
    }

    /**
     * Calculate diff between two arrays
     */
    protected function calculateDiff(array $old, array $new): array
    {
        $diff = [];

        // Find changed and removed fields
        foreach ($old as $key => $oldValue) {
            if (!array_key_exists($key, $new)) {
                $diff[$key] = [
                    'type' => 'removed',
                    'old' => $oldValue,
                    'new' => null,
                ];
            } elseif ($new[$key] !== $oldValue) {
                $diff[$key] = [
                    'type' => 'changed',
                    'old' => $oldValue,
                    'new' => $new[$key],
                ];
            }
        }

        // Find added fields
        foreach ($new as $key => $newValue) {
            if (!array_key_exists($key, $old)) {
                $diff[$key] = [
                    'type' => 'added',
                    'old' => null,
                    'new' => $newValue,
                ];
            }
        }

        return $diff;
    }

    /**
     * Resolve model from type and id
     */
    protected function resolveModel(string $type, int $id)
    {
        return match ($type) {
            'topic' => Topic::find($id),
            'chapter' => Chapter::find($id),
            'section' => Section::find($id),
            default => null,
        };
    }
}
