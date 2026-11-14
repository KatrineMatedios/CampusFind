<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'student_id' => ['required', 'string', 'max:50', 'unique:users,student_id'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'college_program' => ['nullable', 'string', 'max:150'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms_accepted' => ['accepted'],
        ]);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'student_id' => $data['student_id'],
            'email' => $data['email'],
            'college_program' => $data['college_program'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,
            'role' => 'student',
            'password' => $data['password'],
        ]);

        $token = $user->createToken('campusfind')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $identifier = trim($data['identifier']);
        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('student_id', $identifier)
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => ['These credentials do not match our records.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'identifier' => ['This account has been deactivated. Please contact the administrator.'],
            ]);
        }

        $token = $user->createToken($request->boolean('remember') ? 'campusfind-remembered' : 'campusfind')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => new UserResource($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
        ]);

        $identifier = trim($data['identifier']);
        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('student_id', $identifier)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'identifier' => ['We could not find an account with that Student ID or email.'],
            ]);
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'identifier' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => 'A password reset link has been sent to your student email.',
            'email' => $user->email,
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => $password])->setRememberToken(Str::random(60));
            $user->save();
            $user->tokens()->delete();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => 'Password reset successfully. You can now log in.']);
    }
}
