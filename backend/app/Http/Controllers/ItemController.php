<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\ItemResource;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $items = Item::query()
            ->with(['category', 'reporter'])
            ->withCount('claims')
            ->filter($request->only(['type', 'category', 'search', 'status']))
            ->when(! $request->filled('status'), fn ($query) => $query->where('status', '!=', 'resolved'))
            ->latest()
            ->paginate((int) $request->input('per_page', 24));

        return ItemResource::collection($items);
    }

    public function recentLost(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $items = Item::query()
            ->with(['category', 'reporter'])
            ->where('type', 'lost')
            ->where('status', '!=', 'resolved')
            ->latest()
            ->limit(5)
            ->get();

        return ItemResource::collection($items);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'lost' => Item::where('type', 'lost')->where('status', '!=', 'resolved')->count(),
            'found' => Item::where('type', 'found')->where('status', '!=', 'resolved')->count(),
            'resolved' => Item::where('status', 'resolved')->count(),
        ]);
    }

    public function show(Item $item): JsonResponse
    {
        $item->load(['category', 'reporter'])->loadCount('claims');

        return response()->json(['data' => new ItemResource($item)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('items', 'public');
        }

        $item = $request->user()->items()->create($data);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'item_id' => $item->id,
            'action' => 'reported',
            'description' => "Reported a {$item->type} item: {$item->name}",
        ]);

        $item->load(['category', 'reporter']);

        return response()->json(['data' => new ItemResource($item), 'message' => 'Report submitted successfully.'], 201);
    }

    public function update(Request $request, Item $item): JsonResponse
    {
        abort_unless($item->user_id === $request->user()->id, 403, 'You can only update your own reports.');

        $data = $this->validated($request, partial: true);

        if ($request->hasFile('photo')) {
            if ($item->photo) {
                Storage::disk('public')->delete($item->photo);
            }
            $data['photo'] = $request->file('photo')->store('items', 'public');
        }

        $item->update($data);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'item_id' => $item->id,
            'action' => 'updated',
            'description' => "Updated the report for {$item->name}",
        ]);

        $item->load(['category', 'reporter']);

        return response()->json(['data' => new ItemResource($item), 'message' => 'Report updated.']);
    }

    public function destroy(Request $request, Item $item): JsonResponse
    {
        abort_unless($item->user_id === $request->user()->id, 403, 'You can only delete your own reports.');

        if ($item->photo) {
            Storage::disk('public')->delete($item->photo);
        }

        $item->delete();

        return response()->json(['message' => 'Report deleted.']);
    }

    public function myReports(Request $request): JsonResponse
    {
        $query = $request->user()->items()
            ->with(['category', 'reporter'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->latest();

        $items = $query->get();

        return response()->json([
            'data' => ItemResource::collection($items),
            'counts' => [
                'all' => $request->user()->items()->count(),
                'lost' => $request->user()->items()->where('type', 'lost')->count(),
                'found' => $request->user()->items()->where('type', 'found')->count(),
            ],
        ]);
    }

    public function resolvedReports(Request $request): JsonResponse
    {
        $items = $request->user()->items()
            ->with(['category', 'reporter'])
            ->where('status', 'resolved')
            ->latest()
            ->get();

        return response()->json(['data' => ItemResource::collection($items)]);
    }

    public function categories(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return CategoryResource::collection(Category::orderBy('name')->get());
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'type' => [$required, Rule::in(['lost', 'found'])],
            'name' => [$required, 'string', 'max:150'],
            'category_id' => [$required, 'integer', 'exists:categories,id'],
            'date_reported' => [$required, 'date'],
            'location' => [$required, 'string', 'max:200'],
            'description' => [$required, 'string', 'max:3000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);
    }
}
