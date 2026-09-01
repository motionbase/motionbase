<?php

use App\Mcp\Servers\MotionBaseServer;
use App\Mcp\Support\MarkdownBlocks;
use App\Mcp\Tools\AddInteractiveBlock;
use App\Mcp\Tools\CreateInteractive;
use App\Mcp\Tools\CreateSection;
use App\Mcp\Tools\GetSection;
use App\Mcp\Tools\GetTopic;
use App\Mcp\Tools\ListTopics;
use App\Mcp\Tools\UpdateSection;
use App\Models\Chapter;
use App\Models\Media;
use App\Models\Section;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function course(User $owner): array
{
    $topic = Topic::factory()->create(['user_id' => $owner->id, 'title' => 'Easing']);
    $chapter = Chapter::factory()->create(['topic_id' => $topic->id, 'title' => 'Grundlagen']);
    $section = Section::factory()->create([
        'chapter_id' => $chapter->id,
        'title' => 'Was ist Easing?',
        'content' => ['blocks' => [
            ['type' => 'paragraph', 'data' => ['text' => 'Ein <b>Absatz</b>.']],
            ['type' => 'interactive', 'data' => ['url' => '/interactive/7', 'caption' => '', 'height' => 640]],
        ]],
    ]);

    return [$topic, $chapter, $section];
}

it('refuses unauthenticated calls and points at the auth server', function () {
    $response = postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']);

    $response->assertStatus(401);

    // Without this header an MCP client cannot discover where to authenticate.
    expect($response->headers->get('WWW-Authenticate'))
        ->toContain('resource_metadata=')
        ->toContain('/.well-known/oauth-protected-resource/mcp');
});

it('publishes oauth discovery documents', function () {
    $this->get('/.well-known/oauth-authorization-server')
        ->assertOk()
        ->assertJsonPath('code_challenge_methods_supported', ['S256'])
        ->assertJsonPath('grant_types_supported', ['authorization_code', 'refresh_token'])
        ->assertJsonStructure(['issuer', 'authorization_endpoint', 'token_endpoint', 'registration_endpoint']);

    $this->get('/.well-known/oauth-protected-resource')
        ->assertOk()
        ->assertJsonPath('scopes_supported', ['mcp:use']);
});

it('lists only the acting user\'s own courses', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    course($owner);
    Topic::factory()->create(['user_id' => $stranger->id, 'title' => 'Fremdkurs']);

    MotionBaseServer::actingAs($owner)
        ->tool(ListTopics::class)
        ->assertOk()
        ->assertSee('Easing')
        ->assertDontSee('Fremdkurs');
});

it('never reaches another user\'s content, even with the right id', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    [$topic, $chapter, $section] = course($owner);

    $acting = fn () => MotionBaseServer::actingAs($stranger);

    // A record that exists but belongs to someone else must be indistinguishable
    // from one that does not exist, or the tools become an id oracle.
    $acting()->tool(GetTopic::class, ['topic_id' => $topic->id])->assertHasErrors();
    $acting()->tool(GetSection::class, ['section_id' => $section->id])->assertHasErrors();
    $acting()->tool(CreateSection::class, ['chapter_id' => $chapter->id, 'title' => 'Fremd'])->assertHasErrors();
    $acting()->tool(UpdateSection::class, ['section_id' => $section->id, 'title' => 'Fremd'])->assertHasErrors();

    expect($section->fresh()->title)->toBe('Was ist Easing?');
});

it('round-trips markdown through the editor block format', function () {
    $owner = User::factory()->create();
    [, $chapter] = course($owner);

    $markdown = <<<'MD'
    Ein Absatz mit **fett**, *kursiv* und `code`.

    ### Unterüberschrift

    - Erster Punkt
    - Zweiter Punkt

    ```css
    .a { transition-timing-function: ease-out; }
    ```
    MD;

    MotionBaseServer::actingAs($owner)
        ->tool(CreateSection::class, [
            'chapter_id' => $chapter->id,
            'title' => 'Neue Seite',
            'markdown' => $markdown,
        ])->assertOk();

    $section = Section::where('title', 'Neue Seite')->firstOrFail();

    expect(collect($section->content['blocks'])->pluck('type')->all())
        ->toBe(['paragraph', 'header', 'list', 'code']);

    MotionBaseServer::actingAs($owner)
        ->tool(GetSection::class, ['section_id' => $section->id])
        ->assertOk();

    // Compared against the conversion itself - assertSee would be matching the
    // JSON-escaped wire format, where every newline is a literal \n.
    expect(MarkdownBlocks::toMarkdown($section->content['blocks']))->toBe($markdown);
});

it('reports which rich blocks an overwrite destroys', function () {
    $owner = User::factory()->create();
    [, , $section] = course($owner);

    // The interactive block cannot survive a Markdown body, so the tool has to
    // say what it removed rather than silently dropping it.
    MotionBaseServer::actingAs($owner)
        ->tool(UpdateSection::class, ['section_id' => $section->id, 'markdown' => 'Nur Text.'])
        ->assertOk()
        ->assertSee('interactive');

    expect(collect($section->fresh()->content['blocks'])->pluck('type')->all())->toBe(['paragraph']);
});

it('adds interactive blocks but refuses unsafe urls', function () {
    $owner = User::factory()->create();
    [, , $section] = course($owner);

    MotionBaseServer::actingAs($owner)
        ->tool(AddInteractiveBlock::class, [
            'section_id' => $section->id,
            'url' => 'javascript:alert(1)',
        ])->assertHasErrors();

    MotionBaseServer::actingAs($owner)
        ->tool(AddInteractiveBlock::class, [
            'section_id' => $section->id,
            'url' => '/interactive/7',
            'height' => 600,
            'position' => 0,
        ])->assertOk();

    $blocks = $section->fresh()->content['blocks'];

    expect($blocks[0]['type'])->toBe('interactive')
        ->and($blocks[0]['data']['height'])->toBe(600);
});

it('stores interactive graphics off the public disk', function () {
    Storage::fake('local');
    Storage::fake('public');

    $owner = User::factory()->create();

    MotionBaseServer::actingAs($owner)
        ->tool(CreateInteractive::class, [
            'name' => 'Easing Simulator',
            'html' => '<!DOCTYPE html><p>hi</p>',
        ])->assertOk();

    $media = Media::where('type', 'interactive')->firstOrFail();

    expect(Storage::disk('local')->exists($media->path))->toBeTrue()
        ->and(Storage::disk('public')->exists($media->path))->toBeFalse()
        ->and($media->url)->toBe("/interactive/{$media->id}");
});

it('renders the consent screen instead of dying on an unbound view', function () {
    // Passport binds AuthorizationViewResponse only from inside
    // Passport::authorizationView(). Without that call every real client hits
    // "Target [AuthorizationViewResponse] is not instantiable" the moment it
    // starts the OAuth flow - while discovery and token auth still look fine.
    $client = app(Laravel\Passport\ClientRepository::class)->createAuthorizationCodeGrantClient(
        'Consent Test', ['https://claude.ai/api/mcp/auth_callback'], false,
    );

    actingAs(User::factory()->create())
        ->get('/oauth/authorize?' . http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
            'response_type' => 'code',
            'scope' => 'mcp:use',
            'code_challenge' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
            'code_challenge_method' => 'S256',
        ]))
        ->assertOk()
        ->assertSee('Consent Test');
});

it('round-trips markdown tables', function () {
    $markdown = <<<'MD'
    | Kurve | Wirkung | Einsatz |
    | --- | --- | --- |
    | **Ease-Out** | schneller Start | Menüs, Dialoge |
    | *Ease-In* | träger Start | alles was `verschwindet` |
    MD;

    $blocks = MarkdownBlocks::toBlocks($markdown);

    expect($blocks)->toHaveCount(1)
        ->and($blocks[0]['type'])->toBe('table')
        ->and($blocks[0]['data']['withHeadings'])->toBeTrue()
        ->and($blocks[0]['data']['content'][0])->toBe(['Kurve', 'Wirkung', 'Einsatz'])
        ->and($blocks[0]['data']['content'][1][0])->toBe('<b>Ease-Out</b>');

    expect(MarkdownBlocks::toMarkdown($blocks))->toBe($markdown);
});

it('gives a headless table a header row so it survives the round trip', function () {
    // Markdown has no table without a header, so one is invented rather than
    // letting the rows collapse into paragraphs on the way back in.
    $blocks = [['type' => 'table', 'data' => [
        'withHeadings' => false,
        'content' => [['a', 'b'], ['c', 'd']],
    ]]];

    $back = MarkdownBlocks::toBlocks(MarkdownBlocks::toMarkdown($blocks));

    expect($back[0]['type'])->toBe('table')
        ->and($back[0]['data']['content'])->toBe([['', ''], ['a', 'b'], ['c', 'd']]);
});

it('treats tables as editable rather than as a rich block', function () {
    $owner = User::factory()->create();
    [, , $section] = course($owner);

    $section->update(['content' => ['blocks' => [
        ['type' => 'table', 'data' => ['withHeadings' => true, 'content' => [['a'], ['b']]]],
    ]]]);

    // A table reported as rich would tell the model it cannot be edited, and
    // update_section would claim to have destroyed it.
    MotionBaseServer::actingAs($owner)
        ->tool(GetSection::class, ['section_id' => $section->id])
        ->assertOk()
        ->assertDontSee('rich_blocks":[{');

    MotionBaseServer::actingAs($owner)
        ->tool(UpdateSection::class, ['section_id' => $section->id, 'markdown' => "| x |\n| --- |\n| y |"])
        ->assertOk()
        ->assertSee('"dropped_rich_blocks":[]', false);
});
