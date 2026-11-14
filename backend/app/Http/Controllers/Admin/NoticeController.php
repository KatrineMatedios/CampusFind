<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\CampusNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:500'],
        ]);

        $recipients = 0;
        User::where('is_active', true)
            ->chunkById(200, function ($users) use (&$recipients, $data) {
                foreach ($users as $user) {
                    $user->notify(new CampusNotification(
                        $data['title'],
                        $data['message'],
                        '/browse',
                        'general',
                    ));
                    $recipients++;
                }
            });

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'notice_sent',
            'description' => "Sent platform notice \"{$data['title']}\" to {$recipients} users",
        ]);

        return response()->json([
            'message' => "Notice sent to {$recipients} users.",
        ], 201);
    }
}
