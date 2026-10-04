<?php

use Illuminate\Support\Facades\Blade;

test('logo_uses_theme_tokens_without_hex_colors', function () {

    $svg = Blade::render('<x-logo.icon />');

    // No literal colors: every color comes from the logo-* tokens.
    expect($svg)->not->toMatch('/#[0-9a-fA-F]{3,8}\b/');
    expect($svg)->not->toContain('style=');

    expect($svg)
        ->toContain('fill-logo-cup')
        ->toContain('fill-logo-coffee')
        ->toContain('[stop-color:var(--color-logo-leaf)]');
});

test('logo_gradient_id_is_unique_per_render', function () {

    $ids = collect(range(1, 2))
        ->map(fn() => Blade::render('<x-logo.icon />'))
        ->map(fn($svg) => preg_match('/<linearGradient id="([^"]+)"/', $svg, $m) ? $m[1] : null);

    expect($ids->filter())->toHaveCount(2);
    expect($ids->unique())->toHaveCount(2);
});
