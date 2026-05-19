<?php

declare(strict_types=1);

use App\Services\QrRendering\QrRenderer;

beforeEach(function (): void {
    $this->renderer = new QrRenderer;
});

it('renders svg output for valid data', function (): void {
    $svg = $this->renderer->svg('https://example.com');

    expect($svg)
        ->toBeString()
        ->toContain('<svg')
        ->toContain('</svg>');
});

it('renders png binary for valid data', function (): void {
    $png = $this->renderer->png('https://example.com');

    expect($png)->toBeString()->not->toBeEmpty();
    // PNG magic bytes
    expect(substr($png, 0, 4))->toBe("\x89PNG");
});

it('returns base64 png data uri', function (): void {
    $uri = $this->renderer->pngBase64('test');

    expect($uri)->toStartWith('data:image/png;base64,');
});

it('returns base64 svg data uri', function (): void {
    $uri = $this->renderer->svgDataUri('test');

    expect($uri)->toStartWith('data:image/svg+xml;base64,');
});

it('throws on empty data', function (): void {
    $this->renderer->svg('');
})->throws(InvalidArgumentException::class);

it('throws on whitespace-only data', function (): void {
    $this->renderer->png('   ');
})->throws(InvalidArgumentException::class);

it('throws on invalid ecc level', function (): void {
    $this->renderer->svg('test', 'X');
})->throws(InvalidArgumentException::class);

it('accepts all valid ecc levels', function (string $ecc): void {
    $svg = $this->renderer->svg('test', $ecc);
    expect($svg)->toContain('<svg');
})->with(['L', 'M', 'Q', 'H', 'l', 'm', 'q', 'h']);

it('clamps scale to valid range for png', function (): void {
    $png = $this->renderer->png('test', 'M', 0);
    expect(substr($png, 0, 4))->toBe("\x89PNG");
});
