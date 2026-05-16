<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->data['type'] ?? 'system',
                'data' => $n->data,
                'read_at' => $n->read_at instanceof Carbon ? $n->read_at->toIso8601String() : null,
                'created_at' => $n->created_at instanceof Carbon ? $n->created_at->toIso8601String() : now()->toIso8601String(),
            ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification === null) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $notification->markAsRead();

        return response()->json(['unread_count' => $user->unreadNotifications()->count()]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['unread_count' => 0]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->notifications()->where('id', $id)->delete();

        return response()->json(['unread_count' => $user->unreadNotifications()->count()]);
    }
}
