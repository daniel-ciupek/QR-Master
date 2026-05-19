<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\PlanTier;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAiRateLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $tier = $user->effectivePlan();
        $limit = $this->monthlyLimit($tier);

        if ($limit === 0) {
            return response()->json([
                'message' => 'AI features require a Pro plan or higher.',
                'upgrade_url' => route('pricing'),
            ], 403);
        }

        $key = $this->redisKey($user->id);

        if ($limit !== null) {
            // Atomic incr-then-check eliminates race condition between concurrent requests
            $used = (int) Redis::incr($key);
            Redis::expireat($key, $this->endOfMonth());

            if ($used > $limit) {
                Redis::decr($key);

                return response()->json([
                    'message' => "Monthly AI request limit ({$limit}) reached. Resets on the 1st of next month.",
                    'used' => $limit,
                    'limit' => $limit,
                ], 429);
            }
        }

        $response = $next($request);

        if ($limit !== null) {
            $used = (int) Redis::get($key);
            $response->headers->set('X-AI-RateLimit-Limit', (string) $limit);
            $response->headers->set('X-AI-RateLimit-Used', (string) $used);
            $response->headers->set('X-AI-RateLimit-Remaining', (string) max(0, $limit - $used));
        }

        return $response;
    }

    private function monthlyLimit(PlanTier $tier): ?int
    {
        /** @var array<string, int|null> $limits */
        $limits = (array) config('ai.rate_limits', []);

        return match ($tier) {
            PlanTier::Free => (int) ($limits['free'] ?? 0),
            PlanTier::Pro => isset($limits['pro']) ? (int) $limits['pro'] : 50,
            PlanTier::Business => isset($limits['business']) ? (int) $limits['business'] : 500,
            PlanTier::Enterprise => null,
        };
    }

    private function redisKey(int $userId): string
    {
        return 'ai:usage:'.date('Y-m').':'.$userId;
    }

    private function endOfMonth(): int
    {
        return (int) mktime(0, 0, 0, (int) date('m') + 1, 1, (int) date('Y'));
    }
}
