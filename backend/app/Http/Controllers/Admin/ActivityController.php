<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = ActivityLog::query()
            ->with(['user', 'item'])
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->input('action')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->input('user_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $like = '%'.trim($request->input('search')).'%';
                $query->where(fn ($q) => $q
                    ->where('description', 'like', $like)
                    ->orWhere('action', 'like', $like));
            })
            ->latest()
            ->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'data' => collect($logs->items())->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user_id' => $log->user_id,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'full_name' => $log->user->full_name,
                    'role' => $log->user->role,
                    'avatar_url' => $log->user->avatar_url,
                ] : null,
                'item_id' => $log->item_id,
                'item_name' => $log->item?->name,
                'created_at' => $log->created_at?->toISOString(),
            ]),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
