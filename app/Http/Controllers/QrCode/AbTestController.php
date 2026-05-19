<?php

declare(strict_types=1);

namespace App\Http\Controllers\QrCode;

use App\Actions\AbTest\CreateAbTestAction;
use App\Actions\AbTest\CreateVariantAction;
use App\Actions\AbTest\UpdateVariantAction;
use App\Http\Controllers\Controller;
use App\Models\AbVariant;
use App\Models\QrCode;
use App\Services\AbTestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class AbTestController extends Controller
{
    public function index(QrCode $qrCode, CreateAbTestAction $createAction): Response
    {
        Gate::authorize('update', $qrCode);

        $abTest = $qrCode->abTest ?? $createAction->handle($qrCode);

        $abTest->load('variants');
        $totalScans = (int) $abTest->variants->sum('scan_count');
        $totalWeight = (int) $abTest->variants->sum('weight');

        return Inertia::render('QrCode/AbTest/Index', [
            'qrCode' => [
                'id' => $qrCode->id,
                'title' => $qrCode->title,
                'destination_url' => $qrCode->destination_url,
            ],
            'abTest' => [
                'id' => $abTest->id,
                'is_active' => $abTest->is_active,
                'auto_select_winner' => $abTest->auto_select_winner,
                'auto_select_threshold' => $abTest->auto_select_threshold,
                'has_winner' => $abTest->hasWinner(),
            ],
            'variants' => $abTest->variants->map(fn (AbVariant $v) => [
                'id' => $v->id,
                'name' => $v->name,
                'destination_url' => $v->destination_url,
                'weight' => $v->weight,
                'scan_count' => $v->scan_count,
                'is_winner' => $v->is_winner,
                'pct_weight' => $totalWeight > 0 ? round($v->weight / $totalWeight * 100, 1) : 0,
                'pct_scans' => $totalScans > 0 ? round($v->scan_count / $totalScans * 100, 1) : 0,
            ])->all(),
            'totalScans' => $totalScans,
        ]);
    }

    public function updateSettings(Request $request, QrCode $qrCode): RedirectResponse
    {
        Gate::authorize('update', $qrCode);

        $abTest = $qrCode->abTest;
        if ($abTest === null) {
            abort(404);
        }

        $validated = $request->validate([
            'is_active' => ['boolean'],
            'auto_select_winner' => ['boolean'],
            'auto_select_threshold' => ['integer', 'min:10', 'max:1000000'],
        ]);

        $abTest->update($validated);

        return back()->with('success', 'Ustawienia testu A/B zapisane.');
    }

    public function storeVariant(
        Request $request,
        QrCode $qrCode,
        CreateVariantAction $action,
    ): RedirectResponse {
        Gate::authorize('update', $qrCode);

        $abTest = $qrCode->abTest;
        if ($abTest === null) {
            abort(404);
        }

        $v = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'destination_url' => ['required', 'url', 'max:2000', 'regex:/^https?:\/\/.+/'],
            'weight' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $action->handle($abTest, $v['name'], $v['destination_url'], (int) $v['weight']);

        return back()->with('success', 'Wariant dodany.');
    }

    public function updateVariant(
        Request $request,
        QrCode $qrCode,
        AbVariant $variant,
        UpdateVariantAction $action,
    ): RedirectResponse {
        Gate::authorize('update', $qrCode);
        abort_if($variant->abTest?->qr_code_id !== $qrCode->id, 403);

        $v = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'destination_url' => ['required', 'url', 'max:2000', 'regex:/^https?:\/\/.+/'],
            'weight' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $action->handle($variant, $v['name'], $v['destination_url'], (int) $v['weight']);

        return back()->with('success', 'Wariant zaktualizowany.');
    }

    public function destroyVariant(QrCode $qrCode, AbVariant $variant): RedirectResponse
    {
        Gate::authorize('update', $qrCode);
        abort_if($variant->abTest?->qr_code_id !== $qrCode->id, 403);

        $variant->delete();

        return back()->with('success', 'Wariant usunięty.');
    }

    public function selectWinner(
        Request $request,
        QrCode $qrCode,
        AbTestService $service,
    ): RedirectResponse {
        Gate::authorize('update', $qrCode);

        $abTest = $qrCode->abTest;
        if ($abTest === null) {
            abort(404);
        }

        $v = $request->validate(['variant_id' => ['required', 'integer']]);
        /** @var AbVariant $winner */
        $winner = AbVariant::where('ab_test_id', $abTest->id)
            ->where('id', (int) $v['variant_id'])
            ->firstOrFail();

        $service->selectWinner($abTest, $winner);

        return back()->with('success', "Wariant \"{$winner->name}\" wybrany jako zwycięzca.");
    }
}
