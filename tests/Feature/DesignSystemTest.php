<?php

use function Pest\Laravel\get;

it('serves the design system publicly with the current tokens', function () {
    $response = get('/design');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('text/html');

    // The page is the reference other people build graphics against, so a
    // renamed or moved source file has to fail loudly rather than 404 quietly.
    $response->assertSee('MotionBase Design System')
        ->assertSee('--brand: #ff0055', false)
        ->assertSee('motionbase:resize', false);
});

it('serves the reference as a public document, not a personalised page', function () {
    $response = get('/design');

    $response->assertOk();

    // Through the web group this answered with a session cookie and
    // Cache-Control: private, which reads to a fetcher as "not shareable".
    expect($response->headers->get('Cache-Control'))->toContain('public')
        ->and($response->headers->get('Cache-Control'))->not->toContain('private')
        ->and($response->headers->get('X-Robots-Tag'))->toBe('all')
        ->and($response->headers->getCookies())->toBeEmpty();
});

it('lets crawlers and assistants read everything', function () {
    $robots = file_get_contents(public_path('robots.txt'));

    // An empty `Disallow:` means the same thing, but some fetchers want to see
    // a named permission rather than infer one.
    expect($robots)->toContain('Allow: /')
        ->and($robots)->toContain('ClaudeBot')
        ->and($robots)->not->toMatch('/Disallow:\s*\S/');
});

it('keeps the design system tokens in step with the stylesheet', function () {
    $page = file_get_contents(resource_path('interactives/design-system.html'));
    $stylesheet = file_get_contents(resource_path('css/app.css'));

    // The brand colour lives in both places by necessity: standalone graphics
    // cannot reach the app's Tailwind theme. If it moves, both must move.
    expect($stylesheet)->toContain('--color-primary: #ff0055')
        ->and($page)->toContain('--brand: #ff0055');
});
