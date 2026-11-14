<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlagResource;
use App\Models\ActivityLog;
use App\Models\Flag;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FlagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $flags = Flag::query()
            ->with(['item.category', 'reporter', 'resolver'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $like = '%'.trim($request->input('search')).'%';
                $query->where(fn ($q) => $q
                    ->where('reason', 'like', $like)
                    ->orWhere('details', 'like', $like)
                    ->orWhereHas('item', fn ($i) => $i->where('name', 'like', $like)));
            })
            ->latest()
            ->paginate((int) $request->input('per_page', 10));

        return response()->json([
            'data' => FlagResource::collection($flags->items()),
            'meta' => [
                'current_page' => $flags->currentPage(),
                'last_page' => $flags->lastPage(),
                'per_page' => $flags->perPage(),
                'total' => $flags->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'reason' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        $flag = Flag::create([
            ...$data,
            'reported_by' => $request->user()->id,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'item_id' => $flag->item_id,
            'action' => 'flag_created',
            'description' => "Flagged {$flag->item->name}: {$flag->reason}",
        ]);

        return response()->json([
            'data' => new FlagResource($flag->load(['item.category', 'reporter'])),
            'message' => 'Report flagged successfully.',
        ], 201);
    }

    public function update(Request $request, Flag $flag): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'resolved', 'dismissed'])],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['resolved_by'] = in_array($data['status'], ['resolved', 'dismissed'])
            ? $request->user()->id
            : null;
        $data['resolved_at'] = in_array($data['status'], ['resolved', 'dismissed'])
            ? now()
            : null;

        $flag->update($data);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'item_id' => $flag->item_id,
            'action' => $data['status'] === 'resolved' ? 'flag_resolved' : ($data['status'] === 'dismissed' ? 'flag_dismissed' : 'flag_reopened'),
            'description' => "{$data['status']} the flag on {$flag->item->name} ({$flag->reason})",
        ]);

        return response()->json([
            'data' => new FlagResource($flag->load(['item.category', 'reporter', 'resolver'])),
            'message' => "Flag marked as {$data['status']}.",
        ]);
    }
}
