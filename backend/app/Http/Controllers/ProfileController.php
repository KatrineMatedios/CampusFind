<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => new UserResource($user),
            'stats' => $this->stats($user),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'college_program' => ['nullable', 'string', 'max:150'],
            'contact_number' => ['nullable', 'string', 'max:30'],
        ]);

        $user->update($data);

        return response()->json([
            'data' => new UserResource($user->fresh()),
            'message' => 'Personal information updated.',
        ]);
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $user = $request->user();

        if ($user->avatar) {
            Storage::disk()->delete($user->avatar);
        }

        $user->update(['avatar' => $request->file('avatar')->store('avatars')]);

        return response()->json([
            'data' => new UserResource($user->fresh()),
            'message' => 'Profile photo updated.',
        ]);
    }

    public function activity(Request $request): JsonResponse
    {
        $filter = $request->input('filter', 'all');

        $logs = ActivityLog::query()
            ->with('item')
            ->where('user_id', $request->user()->id)
            ->when($filter === 'reports', fn ($q) => $q->where('action', 'reported'))
            ->when($filter === 'updates', fn ($q) => $q->where('action', '!=', 'reported'))
            ->latest()
            ->get()
            ->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'item_id' => $log->item_id,
                'item_name' => $log->item?->name,
                'item_type' => $log->item?->type,
                'item_status' => $log->item?->status,
                'created_at' => $log->created_at,
            ]);

        return response()->json(['data' => $logs]);
    }

    private function stats($user): array
    {
        return [
            'reports' => $user->items()->count(),
            'resolved' => $user->items()->where('status', 'resolved')->count(),
            'lost' => $user->items()->where('type', 'lost')->count(),
            'found' => $user->items()->where('type', 'found')->count(),
            'claims' => $user->claims()->count(),
            'unread_notifications' => $user->unreadNotifications()->count(),
        ];
    }
}
