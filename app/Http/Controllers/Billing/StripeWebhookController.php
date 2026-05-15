<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Enums\PlanTier;
use App\Models\User;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response;

final class StripeWebhookController extends WebhookController
{
    /** @param array<string, mixed> $payload */
    public function handleInvoicePaymentSucceeded(array $payload): Response
    {
        $customerId = $payload['data']['object']['customer'] ?? null;

        if (! is_string($customerId)) {
            return $this->successMethod();
        }

        /** @var User|null $user */
        $user = $this->getUserByStripeId($customerId);

        if ($user === null) {
            return $this->successMethod();
        }

        $priceId = $this->resolvePriceIdFromInvoice($payload);
        $tier = $priceId !== null ? $this->tierFromPriceId($priceId) : null;

        if ($tier !== null) {
            $user->update(['plan_tier' => $tier->value]);
        }

        return $this->successMethod();
    }

    /** @param array<string, mixed> $payload */
    public function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        $customerId = $payload['data']['object']['customer'] ?? null;

        if (! is_string($customerId)) {
            return $this->successMethod();
        }

        /** @var User|null $user */
        $user = $this->getUserByStripeId($customerId);

        if ($user === null) {
            return $this->successMethod();
        }

        $priceId = $payload['data']['object']['items']['data'][0]['price']['id'] ?? null;
        $tier = is_string($priceId) ? $this->tierFromPriceId($priceId) : null;

        if ($tier !== null) {
            $user->update(['plan_tier' => $tier->value]);
        }

        return $this->successMethod();
    }

    /** @param array<string, mixed> $payload */
    public function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $customerId = $payload['data']['object']['customer'] ?? null;

        if (! is_string($customerId)) {
            return $this->successMethod();
        }

        /** @var User|null $user */
        $user = $this->getUserByStripeId($customerId);

        $user?->update(['plan_tier' => PlanTier::Free->value]);

        return $this->successMethod();
    }

    /** @param array<string, mixed> $payload */
    public function handleInvoicePaymentFailed(array $payload): Response
    {
        // Email notification handled in task 8.11 (payment failed email)
        return $this->successMethod();
    }

    /** @param array<string, mixed> $payload */
    private function resolvePriceIdFromInvoice(array $payload): ?string
    {
        $lines = $payload['data']['object']['lines']['data'] ?? [];

        if (! is_array($lines) || $lines === []) {
            return null;
        }

        $priceId = $lines[0]['price']['id'] ?? null;

        return is_string($priceId) ? $priceId : null;
    }

    private function tierFromPriceId(string $priceId): ?PlanTier
    {
        /** @var array<string, array{stripe_price_id: string|null}> $plans */
        $plans = config('plans', []);

        foreach ($plans as $tierValue => $plan) {
            if (($plan['stripe_price_id'] ?? null) === $priceId) {
                return PlanTier::tryFrom($tierValue);
            }
        }

        return null;
    }
}
