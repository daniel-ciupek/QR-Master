<?php

declare(strict_types=1);

use App\Actions\QrCode\CreateQrCodeAction;
use App\Actions\QrCode\DeleteQrCodeAction;
use App\Data\QrCodeData;
use App\Enums\QrCodeType;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function qrForPruning(bool $deletedLongAgo = false): QrCode
{
    $user = User::factory()->create();
    $qr = app(CreateQrCodeAction::class)->handle($user, new QrCodeData(
        title: 'Prunable QR',
        type: QrCodeType::Url,
        destination_url: 'https://example.com',
    ));

    app(DeleteQrCodeAction::class)->handle($qr);

    if ($deletedLongAgo) {
        QrCode::withTrashed()->where('id', $qr->id)->update([
            'deleted_at' => now()->subDays(31),
        ]);
    }

    return $qr;
}

it('soft-deleted record older than 30 days is prunable', function (): void {
    $qr = qrForPruning(deletedLongAgo: true);

    $prunable = QrCode::withTrashed()
        ->whereNotNull('deleted_at')
        ->where('deleted_at', '<=', now()->subDays(30))
        ->pluck('id');

    expect($prunable)->toContain($qr->id);
});

it('soft-deleted record newer than 30 days is not prunable', function (): void {
    $qr = qrForPruning(deletedLongAgo: false);

    $prunable = QrCode::withTrashed()
        ->whereNotNull('deleted_at')
        ->where('deleted_at', '<=', now()->subDays(30))
        ->pluck('id');

    expect($prunable)->not->toContain($qr->id);
});

it('model:prune permanently deletes records older than 30 days', function (): void {
    $old = qrForPruning(deletedLongAgo: true);
    $fresh = qrForPruning(deletedLongAgo: false);

    $this->artisan('model:prune', ['--model' => [QrCode::class]])->assertSuccessful();

    expect(QrCode::withTrashed()->find($old->id))->toBeNull();
    expect(QrCode::withTrashed()->find($fresh->id))->not->toBeNull();
});
