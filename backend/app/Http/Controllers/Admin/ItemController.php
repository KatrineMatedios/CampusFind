<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\ActivityLog;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = Item::query()
            ->with(['category', 'reporter'])
            ->withCount('claims')
            ->filter($request->only(['type', 'category', 'search', 'status']))
            ->latest()
            ->paginate((int) $request->input('per_page', 12));

        return response()->json([
            'data' => ItemResource::collection($items->items()),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function update(Request $request, Item $item): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'date_reported' => ['sometimes', 'date'],
            'location' => ['sometimes', 'string', 'max:200'],
            'description' => ['sometimes', 'string', 'max:3000'],
            'status' => ['sometimes', Rule::in(['open', 'claim_pending', 'resolved'])],
        ]);

        $item->update($data);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'item_id' => $item->id,
            'action' => 'item_updated',
            'description' => "Admin updated the report for {$item->name}",
        ]);

        $item->load(['category', 'reporter']);

        return response()->json([
            'data' => new ItemResource($item),
            'message' => 'Report updated successfully.',
        ]);
    }

    public function updateStatus(Request $request, Item $item): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'claim_pending', 'resolved'])],
        ]);

        $previous = $item->status;
        $item->update($data);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'item_id' => $item->id,
            'action' => 'item_status_updated',
            'description' => "Admin changed status of {$item->name} from {$previous} to {$item->status}",
        ]);

        $item->load(['category', 'reporter']);

        return response()->json([
            'data' => new ItemResource($item),
            'message' => "Report marked as {$item->status}.",
        ]);
    }

    public function destroy(Request $request, Item $item): JsonResponse
    {
        if ($item->photo) {
            Storage::disk()->delete($item->photo);
        }

        $name = $item->name;
        $item->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'item_deleted',
            'description' => "Admin deleted the report for {$name}",
        ]);

        return response()->json(['message' => 'Report deleted successfully.']);
    }
}
