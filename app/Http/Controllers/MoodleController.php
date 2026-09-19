<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Services\LtiContent;
use App\Services\LtiService;
use App\Services\MoodleHtml;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What the MotionBase plugin for Moodle reads: the catalogue a teacher picks
 * from, and a task as the description of a native Moodle assignment.
 *
 * Only a Moodle registered here as an LTI platform gets in. Its plugin signs
 * a short-lived token with the site's own LTI key, and that key is checked
 * against the key set the platform already publishes.
 */
class MoodleController extends Controller
{
    public function __construct(
        private LtiService $ltiService,
    ) {}

    public function catalog(Request $request): JsonResponse
    {
        $this->authenticate($request);

        return response()->json(['topics' => LtiContent::catalog()]);
    }

    public function task(Request $request, Section $section, MoodleHtml $html): JsonResponse
    {
        $this->authenticate($request);

        $chapter = $section->chapter;

        abort_unless($section->task_submission && $section->is_published && $chapter?->is_published, 404);

        return response()->json([
            'id' => $section->id,
            'title' => $section->title,
            'topic' => $chapter->topic->title,
            'chapter' => $chapter->title,
            'submission' => $section->task_submission,
            'html' => $html->render($section),
        ]);
    }

    private function authenticate(Request $request): void
    {
        $token = $request->bearerToken();

        // The address as configured, not as this request arrived: behind a
        // proxy the two can differ in scheme, and the plugin signs the former.
        $audience = rtrim(config('app.url'), '/').'/moodle';

        abort_unless($token && $this->ltiService->decodePlatformToken($token, $audience), 401, 'Unknown or unverified Moodle site.');
    }
}
