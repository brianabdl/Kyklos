<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        return response()->json(UserResource::make($request->user()->load('organization')));
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'full_name'  => 'sometimes|string|max:255',
            'avatar_url' => 'sometimes|nullable|url',
        ]);

        $request->user()->update($request->only('full_name', 'avatar_url'));

        return response()->json(UserResource::make($request->user()->load('organization')));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'role'      => 'sometimes|in:employee,manager',
            'password'  => 'sometimes|string|min:8',
        ]);

        $user = User::create([
            'org_id'        => $request->org_id,
            'full_name'     => $request->full_name,
            'email'         => $request->email,
            'role'          => $request->input('role', 'employee'),
            'password_hash' => $request->filled('password') ? Hash::make($request->password) : null,
            'is_active'     => true,
        ]);

        return response()->json(UserResource::make($user->load('organization')), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $users = User::where('org_id', $request->org_id)
            ->where('role', 'employee')
            ->with('organization')
            ->get();

        return response()->json(['employees' => UserResource::collection($users)]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = User::where('id', $id)->where('org_id', $request->org_id)->firstOrFail();
        return response()->json(UserResource::make($user->load('organization')));
    }

    public function resetPin(Request $request, string $id): JsonResponse
    {
        $request->validate(['new_pin' => 'required|digits:4']);

        $user = User::where('id', $id)->where('org_id', $request->org_id)->firstOrFail();
        $user->update(['pin_hash' => Hash::make($request->new_pin)]);

        return response()->json(['message' => 'PIN reset successfully.']);
    }

    public function updateEmployee(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'role'      => 'sometimes|in:employee,manager',
            'is_active' => 'sometimes|boolean',
        ]);

        $user = User::where('id', $id)->where('org_id', $request->org_id)->firstOrFail();
        $user->update($request->only('role', 'is_active'));

        return response()->json(UserResource::make($user->load('organization')));
    }

}
