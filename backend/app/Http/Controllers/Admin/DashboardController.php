<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Flag;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'lost' => Item::where('type', 'lost')->where('status', '!=', 'resolved')->count(),
            'found' => Item::where('type', 'found')->where('status', '!=', 'resolved')->count(),
            'resolved' => Item::where('status', 'resolved')->count(),
            'total_items' => Item::count(),
            'users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'open_flags' => Flag::where('status', 'open')->count(),
            'pending_claims' => Claim::where('status', 'pending')->count(),
        ]);
    }
}
