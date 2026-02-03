<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Section;
use App\Models\Topic;
use App\Services\LtiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class LtiEmbedController extends Controller
{
    public function __construct(
        private LtiService $ltiService
    ) {}

    public function topic(Request $request, Topic $topic)
    {
        $session = $this->validateSession($request);

        $topic->loadMissing([
            'chapters' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order')
                ->with(['sections' => fn ($sq) => $sq->where('is_published', true)->orderBy('sort_order')]),
        ]);

        $firstSection = $topic->chapters->first()?->sections->first();

        return View::make('lti.embed.topic', [
            'topic' => $topic,
            'activeSection' => $firstSection,
            'session' => $session,
        ]);
    }

    public function section(Request $request, Topic $topic, Section $section)
    {
        $session = $this->validateSession($request);

        // Load topic navigation
        $topic->loadMissing([
            'chapters' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order')
                ->with(['sections' => fn ($sq) => $sq->where('is_published', true)->orderBy('sort_order')]),
        ]);

        return View::make('lti.embed.topic', [
            'topic' => $topic,
            'activeSection' => $section,
            'session' => $session,
        ]);
    }

    public function chapter(Request $request, Topic $topic, Chapter $chapter)
    {
        $session = $this->validateSession($request);

        // Load only this chapter with its sections
        $chapter->loadMissing([
            'sections' => fn ($sq) => $sq->where('is_published', true)->orderBy('sort_order'),
        ]);

        $firstSection = $chapter->sections->first();

        return View::make('lti.embed.chapter', [
            'topic' => $topic,
            'chapter' => $chapter,
            'activeSection' => $firstSection,
            'session' => $session,
        ]);
    }

    public function chat(Request $request, Topic $topic)
    {
        $session = $this->validateSession($request);

        return View::make('lti.embed.chat', [
            'topic' => $topic,
            'session' => $session,
        ]);
    }

    public function picker(Request $request)
    {
        $session = $this->validateSession($request);

        $topics = Topic::with([
            'chapters' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order')
                ->with(['sections' => fn ($sq) => $sq->where('is_published', true)->orderBy('sort_order')]),
        ])->get();

        return View::make('lti.embed.picker', [
            'topics' => $topics,
            'session' => $session,
        ]);
    }

    private function validateSession(Request $request)
    {
        $token = $request->query('lti_session');
        if (! $token) {
            abort(403, 'Missing LTI session');
        }

        $session = $this->ltiService->getSessionByToken($token);
        if (! $session) {
            abort(403, 'Invalid or expired LTI session');
        }

        return $session;
    }
}
