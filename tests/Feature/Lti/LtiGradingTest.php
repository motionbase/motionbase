<?php

use App\Models\Chapter;
use App\Models\LtiPlatform;
use App\Models\LtiQuizAttempt;
use App\Models\LtiResourceLink;
use App\Models\LtiSession;
use App\Models\Section;
use App\Models\Topic;
use App\Services\LtiContent;
use App\Services\LtiGrades;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

const LINEITEM = 'https://moodle.test/mod/lti/services.php/2/lineitems/7/lineitem?type_id=1';

beforeEach(function () {
    // A throwaway key pair: the real ones are not in the repository.
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($key, $private);
    $dir = sys_get_temp_dir().'/lti-'.Str::random(8);
    mkdir($dir);
    file_put_contents("$dir/private.pem", $private);
    file_put_contents("$dir/public.pem", openssl_pkey_get_details($key)['key']);
    config(['lti.private_key_path' => "$dir/private.pem", 'lti.public_key_path' => "$dir/public.pem"]);

    Http::fake([
        '*/mod/lti/token.php' => Http::response(['access_token' => 'token-1', 'token_type' => 'Bearer', 'expires_in' => 3600]),
        '*/mod/lti/services.php/*' => Http::response(['id' => 'https://moodle.test/mod/lti/services.php/2/lineitems/9/lineitem?type_id=1'], 200),
    ]);
});

function moodle(): LtiPlatform
{
    return LtiPlatform::firstOrCreate(['issuer' => 'https://moodle.test'], [
        'name' => 'Moodle', 'client_id' => 'client-1', 'deployment_id' => '1',
        'auth_login_url' => 'https://moodle.test/mod/lti/auth.php',
        'auth_token_url' => 'https://moodle.test/mod/lti/token.php',
        'jwks_url' => 'https://moodle.test/mod/lti/certs.php', 'is_active' => true,
    ]);
}

function launch(array $claims = [], bool $teacher = false): LtiSession
{
    $claims = array_replace([
        'sub' => 'learner-7',
        'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiResourceLinkRequest',
        'https://purl.imsglobal.org/spec/lti/claim/roles' => [
            'http://purl.imsglobal.org/vocab/lis/v2/membership#'.($teacher ? 'Instructor' : 'Learner'),
        ],
        'https://purl.imsglobal.org/spec/lti/claim/resource_link' => ['id' => '42'],
    ], $claims);

    return LtiSession::create([
        'lti_platform_id' => moodle()->id,
        'lti_user_id' => $claims['sub'],
        'resource_link_id' => $claims['https://purl.imsglobal.org/spec/lti/claim/resource_link']['id'] ?? null,
        'claims' => $claims,
        'session_token' => Str::random(64),
        'expires_at' => now()->addHour(),
    ]);
}

function graded(array $custom): array
{
    return [
        'https://purl.imsglobal.org/spec/lti/claim/custom' => $custom,
        LtiGrades::AGS => [
            'scope' => [LtiGrades::SCOPE_LINEITEM, LtiGrades::SCOPE_SCORE],
            'lineitems' => 'https://moodle.test/mod/lti/services.php/2/lineitems?type_id=1',
            'lineitem' => LINEITEM,
        ],
    ];
}

function quizLesson(Chapter $chapter, int $questions = 3, string $blockId = 'check-1'): Section
{
    $items = [];
    for ($i = 1; $i <= $questions; $i++) {
        $items[] = ['id' => "{$blockId}-q{$i}", 'question' => "Frage {$i}", 'answers' => [
            ['id' => "{$blockId}-q{$i}-wrong", 'text' => 'Falsch', 'isCorrect' => false],
            ['id' => "{$blockId}-q{$i}-right", 'text' => 'Richtig', 'isCorrect' => true],
        ]];
    }

    return Section::factory()->create(['chapter_id' => $chapter->id, 'content' => ['blocks' => [
        ['id' => 'intro', 'type' => 'paragraph', 'data' => ['text' => 'Einleitung']],
        ['id' => $blockId, 'type' => 'quiz', 'data' => ['questions' => $items]],
    ]]]);
}

function answers(Section $lesson, string $blockId, array $right): array
{
    $picked = [];
    foreach (collect($lesson->content['blocks'])->firstWhere('id', $blockId)['data']['questions'] as $i => $q) {
        $picked[$q['id']] = in_array($i, $right, true) ? $q['id'].'-right' : $q['id'].'-wrong';
    }

    return $picked;
}

function scoreRequests(): array
{
    return Http::recorded(fn (HttpRequest $r) => str_contains($r->url(), '/scores'))->map(fn ($pair) => $pair[0])->values()->all();
}

// ---------------------------------------------------------------- Auswahl ---

it('lets a teacher choose a chapter, and has Moodle create the grade column when it holds questions', function () {
    $chapter = Chapter::factory()->create();
    quizLesson($chapter, 3);
    $plain = Chapter::factory()->create(['topic_id' => $chapter->topic_id]);
    Section::factory()->create(['chapter_id' => $plain->id]);

    $session = launch([
        'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiDeepLinkingRequest',
        'https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings' => ['deep_link_return_url' => 'https://moodle.test/mod/lti/contentitem_return.php'],
    ], teacher: true);

    $item = function (string $choice) use ($session) {
        $html = post('/lti/deep-linking/return', ['lti_session' => $session->session_token, 'choice' => $choice])
            ->assertOk()->getContent();
        preg_match('/name="JWT" value="([^"]+)"/', $html, $m);
        $claims = (array) JWT::decode($m[1], new Key(file_get_contents(config('lti.public_key_path')), 'RS256'));

        return json_decode(json_encode($claims['https://purl.imsglobal.org/spec/lti-dl/claim/content_items'][0]), true);
    };

    $quiz = $item("chapter:{$chapter->id}");

    expect($quiz['custom']['chapter_id'])->toBe((string) $chapter->id)
        // Ids travel along, so renaming a chapter does not break the activity
        ->and($quiz['custom']['content_type'])->toBe('chapter')
        ->and($quiz['lineItem']['scoreMaximum'])->toBe(3)
        ->and($quiz['text'])->toContain('3 Fragen');

    // Nothing to grade, no column
    expect($item("chapter:{$plain->id}"))->not->toHaveKey('lineItem');
});

it('refuses content that is not published or a session that is not choosing', function () {
    $hidden = Chapter::factory()->create(['is_published' => false]);
    Section::factory()->create(['chapter_id' => $hidden->id]);

    $choosing = launch(['https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiDeepLinkingRequest'], teacher: true);

    post('/lti/deep-linking/return', ['lti_session' => $choosing->session_token, 'choice' => "chapter:{$hidden->id}"])
        ->assertSessionHasErrors('choice');

    $viewing = launch(teacher: true);
    $open = Chapter::factory()->create();
    Section::factory()->create(['chapter_id' => $open->id]);

    post('/lti/deep-linking/return', ['lti_session' => $viewing->session_token, 'choice' => "chapter:{$open->id}"])
        ->assertForbidden();
});

it('lists only what a class could actually see, and counts the questions the grade is out of', function () {
    $topic = Topic::factory()->create(['title' => 'Easing']);
    $chapter = Chapter::factory()->create(['topic_id' => $topic->id]);
    quizLesson($chapter, 2);
    Chapter::factory()->create(['topic_id' => $topic->id, 'title' => 'Leer']);
    Topic::factory()->create(['title' => 'Ohne Inhalt']);

    $catalog = collect(LtiContent::catalog());

    expect($catalog->pluck('title')->all())->toBe(['Easing'])
        ->and($catalog[0]['chapters'])->toHaveCount(1)
        ->and($catalog[0]['questions'])->toBe(2);
});

// ----------------------------------------------------------- Aufruf & Bindung

it('finds the content by id, and still by slug for links made before ids', function () {
    $chapter = Chapter::factory()->create();
    Section::factory()->create(['chapter_id' => $chapter->id]);

    $byId = launch(['https://purl.imsglobal.org/spec/lti/claim/custom' => ['content_type' => 'chapter', 'chapter_id' => (string) $chapter->id]]);
    $bySlug = launch(['https://purl.imsglobal.org/spec/lti/claim/custom' => [
        'content_type' => 'chapter', 'topic_slug' => $chapter->topic->slug, 'chapter_slug' => $chapter->slug,
    ]]);

    // Renamed since: the id still finds it
    $chapter->update(['slug' => 'umbenannt']);

    expect(LtiContent::forSession($byId)?->chapter->id)->toBe($chapter->id)
        ->and(LtiContent::forSession($bySlug))->toBeNull();
});

it('lets a teacher choose content for an activity saved without it, grade column included', function () {
    $chapter = Chapter::factory()->create();
    quizLesson($chapter, 3);
    // Saved without a choice: Moodle offers the column list, but no column of its own
    $session = launch([
        LtiGrades::AGS => [
            'scope' => [LtiGrades::SCOPE_LINEITEM, LtiGrades::SCOPE_SCORE],
            'lineitems' => 'https://moodle.test/mod/lti/services.php/2/lineitems?type_id=1',
        ],
        'https://purl.imsglobal.org/spec/lti/claim/resource_link' => ['id' => '42', 'title' => 'Wochenaufgabe 3'],
    ], teacher: true);

    get("/lti/bind?lti_session={$session->session_token}")->assertOk()->assertSee('Diese Aktivität zeigt noch nichts');

    post('/lti/bind', ['lti_session' => $session->session_token, 'choice' => "chapter:{$chapter->id}"])
        ->assertRedirect();

    $link = LtiResourceLink::firstOrFail();

    // The column carries the name the teacher gave the activity
    $created = Http::recorded(fn (HttpRequest $r) => str_ends_with(parse_url($r->url(), PHP_URL_PATH), '/lineitems'))->first()[0];

    expect($created->data())->toMatchArray(['label' => 'Wochenaufgabe 3', 'scoreMaximum' => 3, 'resourceLinkId' => '42'])
        ->and($link->chapter_id)->toBe($chapter->id)
        ->and($link->lineitem_url)->toContain('/lineitems/9/')
        ->and(LtiContent::forSession(launch())?->chapter->id)->toBe($chapter->id);
});

it('keeps learners out of choosing, and leaves Moodle-chosen content to Moodle', function () {
    $chapter = Chapter::factory()->create();
    Section::factory()->create(['chapter_id' => $chapter->id]);

    get('/lti/bind?lti_session='.launch()->session_token)->assertForbidden();

    $chosen = launch(['https://purl.imsglobal.org/spec/lti/claim/custom' => ['content_type' => 'chapter', 'chapter_id' => (string) $chapter->id]], teacher: true);

    post('/lti/bind', ['lti_session' => $chosen->session_token, 'choice' => "chapter:{$chapter->id}"])
        ->assertStatus(409);
});

// ------------------------------------------------------------- Bewertung ---

it('scores the answers itself and sends the grade to Moodle', function () {
    $chapter = Chapter::factory()->create();
    $lesson = quizLesson($chapter, 3);
    $session = launch(graded(['content_type' => 'chapter', 'chapter_id' => (string) $chapter->id]));

    $response = postJson('/lti/quiz-attempts', [
        'lti_session' => $session->session_token,
        'section_id' => $lesson->id,
        'block_id' => 'check-1',
        'answers' => answers($lesson, 'check-1', [0, 2]),
        // Whatever the browser claims is ignored
        'score' => 3,
    ])->assertOk();

    expect($response->json('counted'))->toBeTrue()
        ->and($response->json('first'))->toBe(['score' => 2, 'max' => 3])
        ->and($response->json('sent'))->toBeTrue();

    $sent = scoreRequests()[0];

    expect($sent->url())->toBe('https://moodle.test/mod/lti/services.php/2/lineitems/7/lineitem/scores?type_id=1')
        ->and($sent->header('Authorization')[0])->toBe('Bearer token-1')
        ->and($sent->data())->toMatchArray([
            'userId' => 'learner-7', 'scoreGiven' => 2, 'scoreMaximum' => 3,
            'activityProgress' => 'Completed', 'gradingProgress' => 'FullyGraded',
        ]);
});

it('counts the first run and treats later ones as practice', function () {
    $chapter = Chapter::factory()->create();
    $lesson = quizLesson($chapter, 3);
    $session = launch(graded(['content_type' => 'chapter', 'chapter_id' => (string) $chapter->id]));
    $attempt = fn (array $right) => postJson('/lti/quiz-attempts', [
        'lti_session' => $session->session_token, 'section_id' => $lesson->id,
        'block_id' => 'check-1', 'answers' => answers($lesson, 'check-1', $right),
    ])->json();

    $attempt([0]);
    $retry = $attempt([0, 1, 2]);

    expect($retry['counted'])->toBeFalse()
        ->and($retry['run'])->toBe(['score' => 3, 'max' => 3])
        ->and($retry['first'])->toBe(['score' => 1, 'max' => 3])
        ->and(collect(scoreRequests())->last()->data()['scoreGiven'])->toBe(1);
});

it('adds up every check in the activity and reports it in progress until all are done', function () {
    $chapter = Chapter::factory()->create();
    $first = quizLesson($chapter, 2, 'check-a');
    $second = quizLesson($chapter, 3, 'check-b');
    $session = launch(graded(['content_type' => 'chapter', 'chapter_id' => (string) $chapter->id]));

    postJson('/lti/quiz-attempts', ['lti_session' => $session->session_token, 'section_id' => $first->id,
        'block_id' => 'check-a', 'answers' => answers($first, 'check-a', [0, 1])]);

    expect(scoreRequests()[0]->data())->toMatchArray(['scoreGiven' => 2, 'scoreMaximum' => 5, 'activityProgress' => 'InProgress']);

    postJson('/lti/quiz-attempts', ['lti_session' => $session->session_token, 'section_id' => $second->id,
        'block_id' => 'check-b', 'answers' => answers($second, 'check-b', [1])]);

    expect(scoreRequests()[1]->data())->toMatchArray(['scoreGiven' => 3, 'scoreMaximum' => 5, 'activityProgress' => 'Completed']);
});

it('never grades a teacher trying the check, nor a check outside the activity', function () {
    $chapter = Chapter::factory()->create();
    $lesson = quizLesson($chapter);
    $elsewhere = quizLesson(Chapter::factory()->create(), 3, 'other');

    $teacher = launch(graded(['content_type' => 'chapter', 'chapter_id' => (string) $chapter->id]), teacher: true);

    postJson('/lti/quiz-attempts', ['lti_session' => $teacher->session_token, 'section_id' => $lesson->id,
        'block_id' => 'check-1', 'answers' => answers($lesson, 'check-1', [0, 1, 2])])
        ->assertOk()->assertJsonPath('recorded', false);

    $learner = launch(graded(['content_type' => 'chapter', 'chapter_id' => (string) $chapter->id]));

    postJson('/lti/quiz-attempts', ['lti_session' => $learner->session_token, 'section_id' => $elsewhere->id,
        'block_id' => 'other', 'answers' => answers($elsewhere, 'other', [0, 1, 2])])
        ->assertNotFound();

    expect(LtiQuizAttempt::count())->toBe(0)
        ->and(scoreRequests())->toBeEmpty();
});

it('reuses the access token and takes a same-second conflict from Moodle in its stride', function () {
    $chapter = Chapter::factory()->create();
    $first = quizLesson($chapter, 1, 'check-a');
    $second = quizLesson($chapter, 1, 'check-b');
    $session = launch(graded(['content_type' => 'chapter', 'chapter_id' => (string) $chapter->id]));

    Http::fake([
        '*/mod/lti/token.php' => Http::response(['access_token' => 'token-1', 'expires_in' => 3600]),
        '*/scores*' => Http::sequence()->push([], 200)->push(['error' => 'earlier timestamp'], 409),
    ]);

    foreach ([[$first, 'check-a'], [$second, 'check-b']] as [$lesson, $block]) {
        $sent = postJson('/lti/quiz-attempts', ['lti_session' => $session->session_token, 'section_id' => $lesson->id,
            'block_id' => $block, 'answers' => answers($lesson, $block, [0])])->json('sent');

        expect($sent)->toBeTrue();
    }

    Http::assertSentCount(3); // one token, two scores
});

// ------------------------------------------------------------ Darstellung ---

it('shows a single chosen lesson without the rest of the topic around it', function () {
    $chapter = Chapter::factory()->create();
    $chosen = Section::factory()->create(['chapter_id' => $chapter->id, 'title' => 'Die gewählte Lektion']);
    Section::factory()->create(['chapter_id' => $chapter->id, 'title' => 'Eine andere Lektion', 'sort_order' => 1]);
    $session = launch(['https://purl.imsglobal.org/spec/lti/claim/custom' => ['content_type' => 'section', 'section_id' => (string) $chosen->id]]);

    get(LtiContent::forSession($session)->url($session->session_token))
        ->assertOk()
        ->assertSee('Die gewählte Lektion')
        ->assertDontSee('Eine andere Lektion');
});

it('does not show a lesson under a topic it does not belong to', function () {
    $lesson = Section::factory()->create();
    $otherTopic = Topic::factory()->create();

    get("/lti/embed/topic/{$otherTopic->slug}/section/{$lesson->slug}?lti_session=".launch()->session_token)
        ->assertNotFound();
});

it('tells the teacher what the class sees and that it is graded', function () {
    $chapter = Chapter::factory()->create(['title' => 'Wissenscheck']);
    quizLesson($chapter, 3);
    $session = launch(graded(['content_type' => 'chapter', 'chapter_id' => (string) $chapter->id]), teacher: true);

    $this->followingRedirects()
        ->get(LtiContent::forSession($session)->url($session->session_token))
        ->assertSee('Nur für Lehrpersonen sichtbar')
        ->assertSee('Wird bewertet – 3 Fragen');

    $learner = launch(graded(['content_type' => 'chapter', 'chapter_id' => (string) $chapter->id]));

    $this->followingRedirects()
        ->get(LtiContent::forSession($learner)->url($learner->session_token))
        ->assertDontSee('Nur für Lehrpersonen sichtbar');
});
