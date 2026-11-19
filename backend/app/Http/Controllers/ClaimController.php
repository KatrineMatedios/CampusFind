<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClaimResource;
use App\Models\ActivityLog;
use App\Models\Claim;
use App\Models\Item;
use App\Notifications\CampusNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClaimController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $claims = Claim::with(['item.category', 'claimant'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->get();

        return response()->json(['data' => ClaimResource::collection($claims)]);
    }

    public function store(Request $request, Item $item): JsonResponse
    {
        abort_unless($item->type === 'found', 422, 'Only found items can be claimed.');
        abort_unless($item->status === 'open', 422, 'This item is no longer available to claim.');
        abort_if($item->user_id === $request->user()->id, 422, 'You cannot claim an item you reported.');

        $data = $request->validate([
            'identifiers' => ['required', 'string', 'min:10', 'max:2000'],
            'proof_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'pickup_location' => ['required', 'string', 'max:200'],
            'pickup_time' => ['required', 'date', 'after:now'],
        ]);

        $existing = $item->claims()
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        abort_if($existing, 422, 'You already have an active claim for this item.');

        if ($request->hasFile('proof_photo')) {
            $data['proof_photo'] = $request->file('proof_photo')->store('claims');
        }

        $claim = $item->claims()->create([
            ...$data,
            'user_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        $item->update(['status' => 'claim_pending']);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'item_id' => $item->id,
            'action' => 'claimed',
            'description' => "Submitted an ownership claim for {$item->name}",
        ]);

        if ($item->reporter && $item->reporter->id !== $request->user()->id) {
            $item->reporter->notify(new CampusNotification(
                'New ownership claim',
                "{$request->user()->full_name} submitted a claim for {$item->name}.",
                "/items/{$item->id}",
                'claim',
            ));
        }

        $claim->load(['item.category', 'claimant']);

        return response()->json([
            'data' => new ClaimResource($claim),
            'message' => 'Your ownership claim has been submitted.',
        ], 201);
    }

    public function show(Request $request, Claim $claim): JsonResponse
    {
        $claim->load(['item.category', 'claimant']);

        $isClaimant = $claim->user_id === $request->user()->id;
        $isReporter = $claim->item?->user_id === $request->user()->id;

        abort_unless($isClaimant || $isReporter || $request->user()->isStaff(), 403);

        return response()->json(['data' => new ClaimResource($claim)]);
    }

    public function updateStatus(Request $request, Claim $claim): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['confirmed', 'rejected', 'collected'])],
        ]);

        $claim->update(['status' => $data['status']]);

        $item = $claim->item;
        $item->update([
            'status' => match ($data['status']) {
                'rejected' => 'open',
                'collected' => 'resolved',
                default => 'claim_pending',
            },
        ]);

        $messages = [
            'confirmed' => 'Your ownership claim was confirmed. Please visit the Lost & Found Desk with your QR code.',
            'rejected' => 'Your ownership claim was not approved. Please contact the Lost & Found Desk if you need help.',
            'collected' => 'Your claim was marked as collected. Thank you!',
        ];

        $claim->claimant?->notify(new CampusNotification(
            'Claim '.$data['status'],
            $messages[$data['status']]." ({$item->name})",
            "/items/{$item->id}",
            'claim',
        ));

        ActivityLog::create([
            'user_id' => $claim->user_id,
            'item_id' => $item->id,
            'action' => $data['status'] === 'collected' ? 'resolved' : 'updated',
            'description' => "Claim for {$item->name} was {$data['status']}",
        ]);

        $claim->load(['item.category', 'claimant']);

        return response()->json([
            'data' => new ClaimResource($claim),
            'message' => 'Claim updated.',
        ]);
    }
}
