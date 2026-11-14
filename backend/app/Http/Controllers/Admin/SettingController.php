<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Setting::query()->pluck('value', 'key'),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach ($data['settings'] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'settings_updated',
            'description' => 'Updated system settings ('.implode(', ', array_keys($data['settings'])).')',
        ]);

        return response()->json([
            'data' => Setting::query()->pluck('value', 'key'),
            'message' => 'Settings saved successfully.',
        ]);
    }
}
