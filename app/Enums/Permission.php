<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
    // QR codes
    case QrCodesCreate = 'qr-codes.create';
    case QrCodesViewOwn = 'qr-codes.view-own';
    case QrCodesEditOwn = 'qr-codes.edit-own';
    case QrCodesDeleteOwn = 'qr-codes.delete-own';
    case QrCodesViewAny = 'qr-codes.view-any';
    case QrCodesDeleteAny = 'qr-codes.delete-any';

    // Analytics
    case AnalyticsViewOwn = 'analytics.view-own';
    case AnalyticsViewAny = 'analytics.view-any';
    case AnalyticsExport = 'analytics.export';

    // API
    case ApiAccess = 'api.access';

    // Admin panel
    case AdminAccess = 'admin.access';

    // Profile / GDPR
    case ProfileExportData = 'profile.export-data';
    case ProfileDeleteAccount = 'profile.delete-account';

    // Billing
    case BillingManage = 'billing.manage';
}
