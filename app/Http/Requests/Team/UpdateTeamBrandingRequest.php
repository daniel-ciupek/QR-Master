<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTeamBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gate::authorize('update', $team) is checked in controller
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'brand_name' => ['nullable', 'string', 'max:60'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'powered_by_hidden' => ['sometimes', 'boolean'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
        ];
    }
}
