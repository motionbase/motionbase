<?php

namespace App\Http\Controllers;

use App\Models\LtiQuizAttempt;
use App\Services\LtiContent;
use App\Services\LtiGrades;
use App\Services\LtiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LtiQuizController extends Controller
{
    public function __construct(
        private LtiService $ltiService,
        private LtiGrades $grades,
    ) {}

    /**
     * A learner finished a knowledge check inside Moodle.
     *
     * The browser sends which answer was picked for each question, never a
     * score: a score from the browser is one devtools edit away from 100 %.
     * The first complete run of each check counts; later runs are practice.
     * Moodle always receives the total over the whole activity, so a report
     * that failed is made good by the next one.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lti_session' => 'required|string',
            'section_id' => 'required|integer',
            'block_id' => 'required|string|max:64',
            'answers' => 'array|max:200',
            'answers.*' => 'nullable|string|max:64',
        ]);

        $session = $this->ltiService->getSessionByToken($validated['lti_session']);

        abort_unless($session, 403, 'Invalid or expired LTI session');

        // Teachers try the check out; that must not land in the gradebook.
        if ($this->ltiService->isInstructor($session)) {
            return response()->json(['recorded' => false, 'reason' => 'instructor']);
        }

        $content = LtiContent::forSession($session);

        // Only checks that belong to this activity count towards its grade.
        $quiz = $content?->quizzes()->first(fn ($quiz) => $quiz['section_id'] === (int) $validated['section_id']
            && $quiz['block_id'] === $validated['block_id']);

        abort_unless($quiz, 404, 'This knowledge check is not part of the activity.');

        $answers = $validated['answers'] ?? [];
        $score = 0;

        foreach ($quiz['questions'] as $index => $question) {
            $picked = $answers[self::questionKey($question, $index)] ?? null;

            foreach (array_values($question['answers'] ?? []) as $answerIndex => $answer) {
                if (! empty($answer['isCorrect']) && $picked === self::answerKey($answer, $answerIndex)) {
                    $score++;
                    break;
                }
            }
        }

        $key = [
            'lti_platform_id' => $session->lti_platform_id,
            'lti_user_id' => $session->lti_user_id,
            'resource_link_id' => (string) $session->resource_link_id,
        ];

        $attempt = LtiQuizAttempt::createOrFirst(
            $key + ['section_id' => $quiz['section_id'], 'block_id' => $quiz['block_id']],
            ['score' => $score, 'max_score' => count($quiz['questions']), 'answers' => $answers],
        );

        // Totals over the checks still in the activity - a check an author
        // removed since no longer counts, in the score or the maximum.
        $attempts = LtiQuizAttempt::where($key)->get()->keyBy(fn ($a) => $a->section_id.':'.$a->block_id);
        $given = 0;
        $done = 0;

        foreach ($content->quizzes() as $check) {
            if ($counted = $attempts->get($check['section_id'].':'.$check['block_id'])) {
                $given += min($counted->score, count($check['questions']));
                $done++;
            }
        }

        $maximum = $content->questionCount();
        $graded = (bool) $this->grades->lineitem($session);
        $complete = $done === $content->quizzes()->count();

        return response()->json([
            'recorded' => true,
            'counted' => $attempt->wasRecentlyCreated,
            'run' => ['score' => $score, 'max' => count($quiz['questions'])],
            'first' => ['score' => $attempt->score, 'max' => $attempt->max_score],
            'total' => ['score' => $given, 'max' => $maximum, 'complete' => $complete],
            'graded' => $graded,
            'sent' => $graded && $this->grades->report($session, $given, $maximum, $complete),
        ]);
    }

    /** Questions and answers carry ids; content from before that falls back to position. */
    public static function questionKey(array $question, int $index): string
    {
        return (string) ($question['id'] ?? 'q'.$index);
    }

    public static function answerKey(array $answer, int $index): string
    {
        return (string) ($answer['id'] ?? 'a'.$index);
    }
}
