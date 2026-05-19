<?php

declare(strict_types=1);

namespace App\Jobs\Api;

use App\Actions\QrCode\CreateQrCodeAction;
use App\Data\QrCodeData;
use App\Enums\QrCodeType;
use App\Models\User;
use App\Services\QrContent\QrContentBuilderService;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class CreateQrCodeBulkItemJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 1;

    /** @param array<string, mixed> $item */
    public function __construct(
        public readonly int $userId,
        public readonly array $item,
    ) {}

    public function handle(CreateQrCodeAction $action, QrContentBuilderService $builder): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $user = User::findOrFail($this->userId);
        $type = QrCodeType::from($this->item['type']);
        $destinationUrl = $builder->build($type, $this->item);

        $data = new QrCodeData(
            title: $this->item['title'],
            type: $type,
            destination_url: $destinationUrl !== '' ? $destinationUrl : ($this->item['destination_url'] ?? null),
            fallback_url: $this->item['fallback_url'] ?? null,
            settings: $this->item['settings'] ?? [],
            is_active: (bool) ($this->item['is_active'] ?? true),
            expires_at: isset($this->item['expires_at']) ? Carbon::parse($this->item['expires_at']) : null,
            activates_at: isset($this->item['activates_at']) ? Carbon::parse($this->item['activates_at']) : null,
            geo_allowed_countries: ! empty($this->item['geo_allowed_countries'])
                ? array_map('strtoupper', (array) $this->item['geo_allowed_countries'])
                : null,
            scan_limit: isset($this->item['scan_limit']) ? (int) $this->item['scan_limit'] : null,
        );

        $qrCode = $action->handle($user, $data);

        if ($data->type === QrCodeType::App) {
            $qrCode->destination_url = route('qr.redirect', $qrCode->short_hash);
            $qrCode->save();
        }

        if (isset($this->item['vcard_phone'])) {
            $qrCode->vcard_phone = $this->item['vcard_phone'];
        }
        if (isset($this->item['vcard_email'])) {
            $qrCode->vcard_email = $this->item['vcard_email'];
        }
        if (isset($this->item['wifi_password'])) {
            $qrCode->wifi_password = $this->item['wifi_password'];
        }

        $qrCode->save();
    }
}
