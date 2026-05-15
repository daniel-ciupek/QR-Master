<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Enums\PlanTier;
use App\Models\AffiliateCommission;
use App\Models\User;
use App\Notifications\Billing\PaymentFailedNotification;
use App\Notifications\Billing\PaymentSucceededNotification;
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

        $amount = $payload['data']['object']['amount_paid'] ?? 0;
        $currency = strtoupper((string) ($payload['data']['object']['currency'] ?? 'PLN'));
        $formatted = number_format((int) $amount / 100, 2).' '.$currency;

        $user->notify(new PaymentSucceededNotification(
            planName: $user->plan_tier->label(),
            amount: $formatted,
        ));

        $this->recordAffiliateCommission($user, (int) $amount, strtoupper((string) ($payload['data']['object']['currency'] ?? 'PLN')), $payload['data']['object']['id'] ?? null);

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
        $customerId = $payload['data']['object']['customer'] ?? null;

        if (! is_string($customerId)) {
            return $this->successMethod();
        }

        /** @var User|null $user */
        $user = $this->getUserByStripeId($customerId);

        $user?->notify(new PaymentFailedNotification(
            planName: $user->plan_tier->label(),
        ));

        return $this->successMethod();
    }

    private function recordAffiliateCommission(User $user, int $amountGross, string $currency, mixed $invoiceId): void
    {
        if ($user->referred_by_user_id === null) {
            return;
        }

        AffiliateCommission::create([
            'user_id' => $user->referred_by_user_id,
            'referred_user_id' => $user->id,
            'amount_gross' => $amountGross,
            'commission_amount' => (int) round($amountGross * 0.20),
            'currency' => $currency,
            'stripe_invoice_id' => is_string($invoiceId) ? $invoiceId : null,
            'status' => 'pending',
        ]);
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
