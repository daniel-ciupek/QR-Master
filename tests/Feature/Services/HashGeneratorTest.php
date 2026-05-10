<?php

declare(strict_types=1);

use App\Services\HashGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates a string of correct length', function (): void {
    $hash = (new HashGenerator)->generate();

    expect($hash)->toHaveLength(8);
});

it('generates only base62 characters', function (): void {
    $gen = new HashGenerator;

    for ($i = 0; $i < 20; $i++) {
        expect($gen->generate())->toMatch('/^[0-9a-zA-Z]{8}$/');
    }
});

it('generates unique hashes across many calls', function (): void {
    $gen = new HashGenerator;
    $hashes = [];

    for ($i = 0; $i < 200; $i++) {
        $hashes[] = $gen->generate();
    }

    expect(array_unique($hashes))->toHaveCount(200);
});
