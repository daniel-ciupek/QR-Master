<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Models\Team;
use Illuminate\Http\UploadedFile;

final class UpdateTeamBrandingAction
{
    /**
     * @param array{
     *   brand_name?: string|null,
     *   primary_color?: string|null,
     *   powered_by_hidden?: bool,
     * } $data
     */
    public function handle(Team $team, array $data, ?UploadedFile $logo = null, bool $removeLogo = false): void
    {
        $settings = $team->settings ?? [];

        if (array_key_exists('brand_name', $data)) {
            $settings['brand_name'] = $data['brand_name'] !== '' ? $data['brand_name'] : null;
        }

        if (array_key_exists('primary_color', $data)) {
            $settings['primary_color'] = $data['primary_color'] !== '' ? $data['primary_color'] : null;
        }

        if (array_key_exists('powered_by_hidden', $data)) {
            $settings['powered_by_hidden'] = $data['powered_by_hidden'];
        }

        $team->update(['settings' => $settings]);

        if ($removeLogo) {
            $team->clearMediaCollection('brand_logo');
        } elseif ($logo !== null) {
            $team->addMedia($logo)
                ->toMediaCollection('brand_logo');
        }
    }
}
