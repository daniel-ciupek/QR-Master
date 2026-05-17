<?php

declare(strict_types=1);

namespace App\Http\Controllers\Scim;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ScimConfigController extends Controller
{
    public function serviceProviderConfig(Request $request, string $teamSlug): JsonResponse
    {
        return response()->json([
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:ServiceProviderConfig'],
            'documentationUri' => '',
            'patch' => ['supported' => true],
            'bulk' => ['supported' => false, 'maxOperations' => 0, 'maxPayloadSize' => 0],
            'filter' => ['supported' => true, 'maxResults' => 100],
            'changePassword' => ['supported' => false],
            'sort' => ['supported' => false],
            'etag' => ['supported' => false],
            'authenticationSchemes' => [[
                'name' => 'OAuth Bearer Token',
                'description' => 'Authentication scheme using the OAuth Bearer Token standard.',
                'type' => 'oauthbearertoken',
                'primary' => true,
            ]],
        ])->header('Content-Type', 'application/scim+json');
    }

    public function schemas(Request $request, string $teamSlug): JsonResponse
    {
        return response()->json([
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
            'totalResults' => 1,
            'Resources' => [[
                'id' => 'urn:ietf:params:scim:schemas:core:2.0:User',
                'name' => 'User',
                'description' => 'User Account',
                'attributes' => [
                    ['name' => 'userName', 'type' => 'string', 'required' => true],
                    ['name' => 'name', 'type' => 'complex', 'required' => false],
                    ['name' => 'emails', 'type' => 'complex', 'required' => false, 'multiValued' => true],
                    ['name' => 'active', 'type' => 'boolean', 'required' => false],
                ],
            ]],
        ])->header('Content-Type', 'application/scim+json');
    }
}
