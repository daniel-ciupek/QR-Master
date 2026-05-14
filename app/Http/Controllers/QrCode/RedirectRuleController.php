<?php

declare(strict_types=1);

namespace App\Http\Controllers\QrCode;

use App\Actions\RedirectRule\CreateRedirectRuleAction;
use App\Actions\RedirectRule\DeleteRedirectRuleAction;
use App\Actions\RedirectRule\ReorderRedirectRulesAction;
use App\Actions\RedirectRule\UpdateRedirectRuleAction;
use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Models\RedirectRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class RedirectRuleController extends Controller
{
    public function index(QrCode $qrCode): Response
    {
        Gate::authorize('update', $qrCode);

        $rules = $qrCode->redirectRules()
            ->orderBy('priority')
            ->get()
            ->map(fn (RedirectRule $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'conditions' => $r->conditions,
                'destination_url' => $r->destination_url,
                'priority' => $r->priority,
                'is_active' => $r->is_active,
            ])
            ->all();

        return Inertia::render('QrCode/Rules/Index', [
            'qrCode' => [
                'id' => $qrCode->id,
                'title' => $qrCode->title,
                'short_hash' => $qrCode->short_hash,
                'destination_url' => $qrCode->destination_url,
            ],
            'rules' => $rules,
        ]);
    }

    public function store(
        Request $request,
        QrCode $qrCode,
        CreateRedirectRuleAction $action,
    ): RedirectResponse {
        Gate::authorize('update', $qrCode);

        $validated = $this->validateRule($request);

        $action->handle(
            $qrCode,
            $validated['name'] ?? null,
            $validated['conditions'],
            $validated['destination_url'],
        );

        return back()->with('success', 'Reguła dodana.');
    }

    public function update(
        Request $request,
        QrCode $qrCode,
        RedirectRule $rule,
        UpdateRedirectRuleAction $action,
    ): RedirectResponse {
        Gate::authorize('update', $qrCode);
        abort_if($rule->qr_code_id !== $qrCode->id, 403);

        $validated = $this->validateRule($request);

        $action->handle(
            $rule,
            $validated['name'] ?? null,
            $validated['conditions'],
            $validated['destination_url'],
            (bool) ($validated['is_active'] ?? true),
        );

        return back()->with('success', 'Reguła zaktualizowana.');
    }

    public function destroy(
        QrCode $qrCode,
        RedirectRule $rule,
        DeleteRedirectRuleAction $action,
    ): RedirectResponse {
        Gate::authorize('update', $qrCode);
        abort_if($rule->qr_code_id !== $qrCode->id, 403);

        $action->handle($rule);

        return back()->with('success', 'Reguła usunięta.');
    }

    public function reorder(
        Request $request,
        QrCode $qrCode,
        ReorderRedirectRulesAction $action,
    ): RedirectResponse {
        Gate::authorize('update', $qrCode);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $action->handle($qrCode, $validated['ids']);

        return back();
    }

    /** @return array<string, mixed> */
    private function validateRule(Request $request): array
    {
        return $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'destination_url' => ['required', 'url', 'max:2000', 'regex:/^https?:\/\/.+/'],
            'is_active' => ['boolean'],
            'conditions' => ['required', 'array', 'min:1'],
            'conditions.*.type' => ['required', Rule::in(['device', 'country', 'language', 'time_range', 'user_agent'])],
            'conditions.*.operator' => ['nullable', 'string', 'max:20'],
            'conditions.*.value' => ['nullable'],
            'conditions.*.from' => ['nullable', 'string', 'max:5'],
            'conditions.*.to' => ['nullable', 'string', 'max:5'],
            'conditions.*.days' => ['nullable', 'array'],
            'conditions.*.days.*' => ['integer', 'between:1,7'],
            'conditions.*.timezone' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
