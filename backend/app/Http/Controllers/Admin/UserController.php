<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $like = '%'.trim($request->input('search')).'%';
                $query->where(fn ($q) => $q
                    ->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('student_id', 'like', $like));
            })
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->input('role')))
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->input('status') === 'active') {
                    $q->where('is_active', true);
                } elseif ($request->input('status') === 'inactive') {
                    $q->where('is_active', false);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate((int) $request->input('per_page', 10));

        return response()->json([
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'student_id' => ['required', 'string', 'max:50', 'unique:users,student_id'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'college_program' => ['nullable', 'string', 'max:150'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(['student', 'staff', 'admin'])],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create($data);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user_created',
            'description' => "Created account for {$user->full_name} ({$user->role})",
        ]);

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User created successfully.',
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'student_id' => ['sometimes', 'string', 'max:50', Rule::unique('users', 'student_id')->ignore($user->id)],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'college_program' => ['nullable', 'string', 'max:150'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'role' => ['sometimes', Rule::in(['student', 'staff', 'admin'])],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user_updated',
            'description' => "Updated account for {$user->full_name}",
        ]);

        return response()->json([
            'data' => new UserResource($user->fresh()),
            'message' => 'User updated successfully.',
        ]);
    }

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        abort_if(
            $user->id === $request->user()->id && ! $request->boolean('is_active'),
            422,
            'You cannot deactivate your own account.',
        );

        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $user->update($data);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => $user->is_active ? 'user_activated' : 'user_deactivated',
            'description' => ($user->is_active ? 'Activated' : 'Deactivated')." account for {$user->full_name}",
        ]);

        return response()->json([
            'data' => new UserResource($user->fresh()),
            'message' => $user->is_active ? 'Account activated.' : 'Account deactivated.',
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        abort_if($user->id === $request->user()->id, 422, 'You cannot delete your own account.');

        $name = $user->full_name;
        $user->tokens()->delete();
        $user->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user_deleted',
            'description' => "Deleted account for {$name}",
        ]);

        return response()->json(['message' => 'User deleted successfully.']);
    }
}
