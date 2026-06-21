<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'guard_name' => ['nullable', 'string', 'in:platform,supplier,distributor'],
        ]);

        $guardName = $validated['guard_name'] ?? 'platform';

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['邮箱或密码错误'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['账户已被禁用'],
            ]);
        }

        if ($user->type !== $guardName && $guardName !== 'platform') {
            throw ValidationException::withMessages([
                'email' => ['该账户不属于此登录端'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $roles = $user->roles->map(fn ($role) => [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->toArray(),
        ]);

        $permissions = $user->getAllPermissions()->pluck('name')->toArray();

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'type' => $user->type,
                    'guard_name' => $user->type,
                    'avatar' => $user->avatar,
                    'roles' => $roles,
                    'permissions' => $permissions,
                ],
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => '退出成功']);
    }

    public function user(Request $request)
    {
        $user = $request->user();

        $roles = $user->roles->map(fn ($role) => [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->toArray(),
        ]);

        $permissions = $user->getAllPermissions()->pluck('name')->toArray();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => $user->type,
                'guard_name' => $user->type,
                'avatar' => $user->avatar,
                'roles' => $roles,
                'permissions' => $permissions,
            ],
        ]);
    }
}
