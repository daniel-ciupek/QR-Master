<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            SpatiePermission::findOrCreate($permission->value);
        }

        $userPermissions = [
            Permission::QrCodesCreate->value,
            Permission::QrCodesViewOwn->value,
            Permission::QrCodesEditOwn->value,
            Permission::QrCodesDeleteOwn->value,
            Permission::AnalyticsViewOwn->value,
            Permission::AnalyticsExport->value,
            Permission::ApiAccess->value,
            Permission::ProfileExportData->value,
            Permission::ProfileDeleteAccount->value,
            Permission::BillingManage->value,
        ];

        $adminOnlyPermissions = [
            Permission::QrCodesViewAny->value,
            Permission::QrCodesDeleteAny->value,
            Permission::AnalyticsViewAny->value,
            Permission::AdminAccess->value,
        ];

        $userRole = SpatieRole::findOrCreate(Role::User->value);
        $userRole->syncPermissions($userPermissions);

        $adminRole = SpatieRole::findOrCreate(Role::Admin->value);
        $adminRole->syncPermissions([...$userPermissions, ...$adminOnlyPermissions]);
    }
}
