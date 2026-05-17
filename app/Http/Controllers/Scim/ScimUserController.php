<?php

declare(strict_types=1);

namespace App\Http\Controllers\Scim;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class ScimUserController extends Controller
{
    private const SCIM_USER_SCHEMA = 'urn:ietf:params:scim:schemas:core:2.0:User';

    private const SCIM_LIST_SCHEMA = 'urn:ietf:params:scim:api:messages:2.0:ListResponse';

    private const SCIM_ERROR_SCHEMA = 'urn:ietf:params:scim:api:messages:2.0:Error';

    public function index(Request $request): JsonResponse
    {
        $team = $this->team($request);

        $query = $team->members();

        if ($request->filled('filter')) {
            $filter = $request->string('filter')->toString();
            if (preg_match('/userName eq "([^"]+)"/i', $filter, $m)) {
                $query->where('users.email', $m[1]);
            }
        }

        $startIndex = max(1, (int) $request->input('startIndex', 1));
        $count = min(100, max(1, (int) $request->input('count', 100)));
        $members = $query->get();
        $total = $members->count();
        $paged = $members->slice($startIndex - 1, $count);

        return $this->scim([
            'schemas' => [self::SCIM_LIST_SCHEMA],
            'totalResults' => $total,
            'startIndex' => $startIndex,
            'itemsPerPage' => $count,
            'Resources' => $paged->map(fn (User $u) => $this->userResource($u, $team))->values(),
        ]);
    }

    public function show(Request $request, string $teamSlug, string $userId): JsonResponse
    {
        $team = $this->team($request);
        $user = User::find($userId);

        if ($user === null || ! $team->hasMember($user)) {
            return $this->error(404, 'User not found');
        }

        return $this->scim($this->userResource($user, $team));
    }

    public function store(Request $request): JsonResponse
    {
        $team = $this->team($request);
        $body = (array) $request->json()->all();

        $email = strtolower((string) ($body['userName'] ?? $body['emails'][0]['value'] ?? ''));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error(400, 'Invalid or missing userName (email)');
        }

        $name = $this->extractName($body);
        $active = (bool) ($body['active'] ?? true);

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name ?: $email,
                'password' => bcrypt(Str::random(32)),
                'email_verified_at' => now(),
                'onboarding_completed_at' => now(),
            ]
        );

        if (! $team->hasMember($user)) {
            $team->members()->attach($user->id, [
                'role' => 'viewer',
                'joined_at' => now(),
            ]);
        }

        AuditLogger::log($team, 'scim.user.created', "SCIM provisioned user {$email}.", null, $user);

        return $this->scim($this->userResource($user, $team), 201);
    }

    public function update(Request $request, string $teamSlug, string $userId): JsonResponse
    {
        $team = $this->team($request);
        $user = User::find($userId);

        if ($user === null || ! $team->hasMember($user)) {
            return $this->error(404, 'User not found');
        }

        $body = (array) $request->json()->all();
        $isScimPatch = isset($body['Operations']);

        if ($isScimPatch) {
            foreach ((array) ($body['Operations'] ?? []) as $op) {
                $this->applyPatchOp($user, $team, (array) $op);
            }
        } else {
            $name = $this->extractName($body);

            if ($name !== '') {
                $user->update(['name' => $name]);
            }

            if (isset($body['active']) && ! (bool) $body['active']) {
                $team->members()->detach($user->id);
                AuditLogger::log($team, 'scim.user.deprovisioned', "SCIM deprovisioned user {$user->email}.", null, $user);

                return $this->scim($this->userResource($user, $team, false));
            }
        }

        return $this->scim($this->userResource($user, $team));
    }

    public function destroy(Request $request, string $teamSlug, string $userId): JsonResponse
    {
        $team = $this->team($request);
        $user = User::find($userId);

        if ($user === null || ! $team->hasMember($user)) {
            return $this->error(404, 'User not found');
        }

        $team->members()->detach($user->id);

        AuditLogger::log($team, 'scim.user.deleted', "SCIM removed user {$user->email} from workspace.", null, $user);

        return response()->json(null, 204)->header('Content-Type', 'application/scim+json');
    }

    /** @return array<string, mixed> */
    private function userResource(User $user, Team $team, ?bool $active = null): array
    {
        $isMember = $active ?? $team->hasMember($user);
        [$givenName, $familyName] = $this->splitName($user->name);

        return [
            'schemas' => [self::SCIM_USER_SCHEMA],
            'id' => (string) $user->id,
            'externalId' => $user->email,
            'userName' => $user->email,
            'name' => [
                'formatted' => $user->name,
                'givenName' => $givenName,
                'familyName' => $familyName,
            ],
            'emails' => [['value' => $user->email, 'primary' => true]],
            'active' => $isMember,
            'meta' => [
                'resourceType' => 'User',
                'created' => $user->created_at?->toIso8601String() ?? '',
                'lastModified' => $user->updated_at?->toIso8601String() ?? '',
                'location' => route('scim.users.show', ['teamSlug' => $team->slug, 'userId' => $user->id]),
            ],
        ];
    }

    /** @param array<string, mixed> $op */
    private function applyPatchOp(User $user, Team $team, array $op): void
    {
        $type = strtolower((string) ($op['op'] ?? ''));
        $path = (string) ($op['path'] ?? '');
        $value = $op['value'] ?? null;

        if ($type === 'replace' && ($path === 'active' || (is_array($value) && isset($value['active'])))) {
            $activeVal = $path === 'active' ? $value : $value['active'];

            if (! (bool) $activeVal) {
                $team->members()->detach($user->id);
                AuditLogger::log($team, 'scim.user.deprovisioned', "SCIM deprovisioned {$user->email}.", null, $user);
            } elseif (! $team->hasMember($user)) {
                $team->members()->attach($user->id, ['role' => 'viewer', 'joined_at' => now()]);
            }
        }
    }

    /** @param array<string, mixed> $body */
    private function extractName(array $body): string
    {
        if (isset($body['name']['formatted'])) {
            return (string) $body['name']['formatted'];
        }

        $given = (string) ($body['name']['givenName'] ?? '');
        $family = (string) ($body['name']['familyName'] ?? '');

        return trim("{$given} {$family}");
    }

    /** @return array{0: string, 1: string} */
    private function splitName(string $name): array
    {
        $parts = explode(' ', trim($name), 2);

        return [$parts[0], $parts[1] ?? ''];
    }

    private function team(Request $request): Team
    {
        /** @var Team $team */
        $team = $request->attributes->get('scim_team');

        return $team;
    }

    /** @param array<string, mixed> $data */
    private function scim(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status)
            ->header('Content-Type', 'application/scim+json');
    }

    private function error(int $status, string $detail): JsonResponse
    {
        return response()->json([
            'schemas' => [self::SCIM_ERROR_SCHEMA],
            'status' => (string) $status,
            'detail' => $detail,
        ], $status)->header('Content-Type', 'application/scim+json');
    }
}
