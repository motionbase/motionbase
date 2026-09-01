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

it('keeps the design system tokens in step with the stylesheet', function () {
    $page = file_get_contents(resource_path('interactives/design-system.html'));
    $stylesheet = file_get_contents(resource_path('css/app.css'));

    // The brand colour lives in both places by necessity: standalone graphics
    // cannot reach the app's Tailwind theme. If it moves, both must move.
    expect($stylesheet)->toContain('--color-primary: #ff0055')
        ->and($page)->toContain('--brand: #ff0055');
});
