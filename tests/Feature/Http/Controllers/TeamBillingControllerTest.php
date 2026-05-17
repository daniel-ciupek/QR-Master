<?php

declare(strict_types=1);

use App\Enums\PlanTier;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeTeamWithOwner(): array
{
    $owner = User::factory()->create([
        'plan_tier' => PlanTier::Pro,
        'onboarding_completed_at' => now(),
    ]);
    $team = Team::create([
        'owner_id' => $owner->id,
        'name' => 'Test Team',
        'slug' => 'test-team',
        'plan_tier' => PlanTier::Free->value,
        'settings' => [],
    ]);
    $team->members()->attach($owner->id, ['role' => TeamRole::Owner->value, 'joined_at' => now()]);
    $owner->update(['current_team_id' => $team->id]);

    return [$owner, $team];
}

it('owner can view team billing page', function (): void {
    [$owner, $team] = makeTeamWithOwner();

    $response = $this->actingAs($owner)->get(route('workspaces.billing.show', $team));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Workspace/Billing')
            ->has('plan')
            ->has('planName')
            ->has('subscribed')
        );
});

it('non-owner member cannot view team billing page', function (): void {
    [$owner, $team] = makeTeamWithOwner();

    $editor = User::factory()->create(['onboarding_completed_at' => now()]);
    $team->members()->attach($editor->id, ['role' => TeamRole::Editor->value, 'joined_at' => now()]);

    $response = $this->actingAs($editor)->get(route('workspaces.billing.show', $team));

    $response->assertForbidden();
});

it('guest cannot view team billing page', function (): void {
    [, $team] = makeTeamWithOwner();

    $this->get(route('workspaces.billing.show', $team))->assertRedirect(route('login'));
});

it('effectivePlan returns personal plan outside team context', function (): void {
    $user = User::factory()->create(['plan_tier' => PlanTier::Pro]);

    app()->forgetInstance('current.team');

    expect($user->effectivePlan())->toBe(PlanTier::Pro);
});

it('effectivePlan returns team plan inside team context', function (): void {
    [$owner, $team] = makeTeamWithOwner();
    $team->update(['plan_tier' => PlanTier::Business]);

    app()->instance('current.team', $team);

    expect($owner->effectivePlan())->toBe(PlanTier::Business);

    app()->forgetInstance('current.team');
});

it('team plan_tier defaults to free', function (): void {
    [, $team] = makeTeamWithOwner();

    expect($team->plan_tier)->toBe(PlanTier::Free);
});
