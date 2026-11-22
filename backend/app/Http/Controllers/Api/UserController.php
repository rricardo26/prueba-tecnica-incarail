<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class UserController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(User::with('roles', 'permissions')->orderBy('id')->get());
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->syncRoles([$data['role']]);

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user->load('roles', 'permissions'));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = $request->validated();

        $payload = collect($data)->only(['name', 'email', 'is_active'])->toArray();
        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        if ($user->is_active === false) {
            $user->tokens()->each(fn ($token) => $token->revoke());
        }

        return new UserResource($user->fresh('roles', 'permissions'));
    }

    public function toggleStatus(Request $request, User $user): UserResource|JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SELF_DEACTIVATION_FORBIDDEN',
                    'message' => 'No puedes desactivar tu propio usuario.',
                    'details' => null,
                ],
            ], 422);
        }

        $user->update(['is_active' => ! $user->is_active]);

        if (! $user->is_active) {
            $user->tokens()->each(fn ($token) => $token->revoke());
        }

        return new UserResource($user);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SELF_DELETE_FORBIDDEN',
                    'message' => 'No puedes eliminar tu propio usuario.',
                    'details' => null,
                ],
            ], 422);
        }

        $user->tokens()->each(fn ($token) => $token->revoke());
        $user->delete();

        return response()->json(null, 204);
    }
}
