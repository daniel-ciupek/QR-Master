<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Billing\StartProTrialAction;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

final class StartProTrialOnRegistration
{
    public function __construct(private readonly StartProTrialAction $action) {}

    public function handle(Registered $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->action->handle($event->user);
    }
}
