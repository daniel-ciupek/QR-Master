<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\QrCode;
use App\Models\RedirectRule;
use Carbon\Carbon;
use Illuminate\Http\Request;

final class SmartRedirectService
{
    public function __construct(private readonly GeoLookupService $geo) {}

    /**
     * Evaluate redirect rules for the given QrCode and request.
     * Returns the matched rule's URL, or null if no rule matches (fall back to destination_url).
     */
    public function resolve(QrCode $qrCode, Request $request): ?string
    {
        $rules = $qrCode->redirectRules()
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();

        foreach ($rules as $rule) {
            if ($this->evaluateRule($rule, $request)) {
                return $rule->destination_url;
            }
        }

        return null;
    }

    private function evaluateRule(RedirectRule $rule, Request $request): bool
    {
        foreach ($rule->conditions as $condition) {
            if (! $this->evaluateCondition($condition, $request)) {
                return false; // AND logic — all conditions must match
            }
        }

        return true;
    }

    /** @param array<string, mixed> $condition */
    private function evaluateCondition(array $condition, Request $request): bool
    {
        return match ($condition['type'] ?? '') {
            'device' => $this->matchDevice($condition, $request),
            'country' => $this->matchCountry($condition, $request),
            'language' => $this->matchLanguage($condition, $request),
            'time_range' => $this->matchTimeRange($condition),
            'user_agent' => $this->matchUserAgent($condition, $request),
            default => false,
        };
    }

    // ── Condition evaluators ────────────────────────────────────────────

    /** @param array<string, mixed> $cond */
    private function matchDevice(array $cond, Request $request): bool
    {
        $ua = strtolower($request->userAgent() ?? '');
        $value = (string) ($cond['value'] ?? '');
        $operator = (string) ($cond['operator'] ?? 'is');

        $detected = $this->detectDevice($ua);
        $matches = $detected === $value || ($value === 'mobile' && in_array($detected, ['ios', 'android'], true));

        return $operator === 'is_not' ? ! $matches : $matches;
    }

    private function detectDevice(string $ua): string
    {
        if (str_contains($ua, 'iphone') || str_contains($ua, 'ipod')) {
            return 'ios';
        }
        if (str_contains($ua, 'ipad')) {
            return 'tablet';
        }
        if (str_contains($ua, 'android')) {
            return str_contains($ua, 'mobile') ? 'android' : 'tablet';
        }
        if (preg_match('/tablet|kindle|silk|playbook/i', $ua)) {
            return 'tablet';
        }

        return 'desktop';
    }

    /** @param array<string, mixed> $cond */
    private function matchCountry(array $cond, Request $request): bool
    {
        $operator = (string) ($cond['operator'] ?? 'in');
        $values = array_map('strtoupper', (array) ($cond['value'] ?? []));

        $geo = $this->geo->lookup($request->ip() ?? '');
        $country = strtoupper((string) ($geo['country'] ?? ''));

        if ($country === '') {
            return false;
        }

        $inList = in_array($country, $values, true);

        return $operator === 'not_in' ? ! $inList : $inList;
    }

    /** @param array<string, mixed> $cond */
    private function matchLanguage(array $cond, Request $request): bool
    {
        $operator = (string) ($cond['operator'] ?? 'starts_with');
        $value = strtolower(trim((string) ($cond['value'] ?? '')));

        $acceptLanguage = strtolower($request->header('Accept-Language') ?? '');
        // Primary language tag — e.g., "pl-PL,pl;q=0.9,en" → "pl"
        $primary = strtolower(explode(',', explode(';', $acceptLanguage)[0])[0]);

        if ($primary === '') {
            return false;
        }

        return match ($operator) {
            'is' => $primary === $value || str_starts_with($primary, $value.'-'),
            'starts_with' => str_starts_with($primary, $value),
            default => false,
        };
    }

    /** @param array<string, mixed> $cond */
    private function matchTimeRange(array $cond): bool
    {
        $tz = (string) ($cond['timezone'] ?? 'UTC');
        $from = (string) ($cond['from'] ?? '00:00');
        $to = (string) ($cond['to'] ?? '23:59');
        $days = (array) ($cond['days'] ?? [1, 2, 3, 4, 5, 6, 7]);

        try {
            $now = Carbon::now($tz);
        } catch (\Exception) {
            $now = Carbon::now('UTC');
        }

        // ISO 8601: 1=Monday … 7=Sunday — format('N') returns string, safe to cast
        $currentDay = (int) $now->format('N');
        if (! in_array($currentDay, array_map('intval', $days), true)) {
            return false;
        }

        $currentTime = $now->format('H:i');

        return $currentTime >= $from && $currentTime <= $to;
    }

    /** @param array<string, mixed> $cond */
    private function matchUserAgent(array $cond, Request $request): bool
    {
        $operator = (string) ($cond['operator'] ?? 'contains');
        $value = strtolower(trim((string) ($cond['value'] ?? '')));
        $ua = strtolower($request->userAgent() ?? '');

        if ($value === '') {
            return false;
        }

        $contains = str_contains($ua, $value);

        return $operator === 'not_contains' ? ! $contains : $contains;
    }
}
