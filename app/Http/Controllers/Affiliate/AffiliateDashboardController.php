<?php

declare(strict_types=1);

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCommission;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AffiliateDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $commissions = AffiliateCommission::where('user_id', $user->id)
            ->with('referredUser:id,name,email,created_at')
            ->orderByDesc('created_at')
            ->get(['id', 'referred_user_id', 'amount_gross', 'commission_amount', 'currency', 'status', 'created_at']);

        $totalPending = AffiliateCommission::where('user_id', $user->id)
            ->where('status', 'pending')
            ->sum('commission_amount');

        $totalPaid = AffiliateCommission::where('user_id', $user->id)
            ->where('status', 'paid')
            ->sum('commission_amount');

        $referralCount = User::where('referred_by_user_id', $user->id)->count();

        return Inertia::render('Affiliate/Dashboard', [
            'referralCode' => $user->referral_code,
            'referralUrl' => url('/register').'?ref='.$user->referral_code,
            'referralCount' => $referralCount,
            'totalPendingPln' => $totalPending / 100,
            'totalPaidPln' => $totalPaid / 100,
            'commissions' => $commissions,
        ]);
    }
}
