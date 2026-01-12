<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();
            
            $token = $user->createToken('api-token', ['*'], now()->addHours(1))->plainTextToken;

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role, // Добавляем роль в ответ
                    'permissions' => $this->getUserPermissions($user->role) // Добавляем права
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'expires_at' => now()->addHours(1)->toISOString(),
                'message' => 'Успішний вхід',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Невірний email або пароль'
        ], 401);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|in:manager,admin,superadmin', // Только для суперадминов
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // По умолчанию создаем менеджера
            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role ?? 'manager',
            ];

            // Проверяем, может ли текущий пользователь создавать пользователей
            $currentUser = $request->user();
            if ($currentUser) {
                // Суперадмин может создавать любых пользователей
                if ($currentUser->isSuperAdmin()) {
                    $data['role'] = $request->role ?? 'manager';
                } 
                // Админ может создавать только менеджеров
                else if ($currentUser->isAdmin() && (!$request->role || $request->role === 'manager')) {
                    $data['role'] = 'manager';
                }
                // Менеджер не может создавать пользователей
                else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient permissions to create users'
                    ], 403);
                }
            }

            $user = User::create($data);

            $token = $user->createToken('api-token', ['*'], now()->addHours(1))->plainTextToken;

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
                ],
                'token' => $token,
                'expires_at' => now()->addHours(1)->toISOString(),
                'message' => 'Користувача успішно зареєстровано',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateUser(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $currentUser = $request->user();

            // Проверка прав на обновление
            if (!$this->canManageUser($currentUser, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to update this user'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
                'password' => 'sometimes|string|min:8',
                'role' => 'sometimes|in:manager,admin,superadmin',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = $request->only(['name', 'email']);
            
            if ($request->has('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            // Проверка прав на изменение роли
            if ($request->has('role')) {
                if ($currentUser->isSuperAdmin()) {
                    $updateData['role'] = $request->role;
                } else if ($currentUser->isAdmin() && $request->role === 'manager') {
                    $updateData['role'] = 'manager';
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient permissions to change role'
                    ], 403);
                }
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
                ],
                'message' => 'Користувача оновлено',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUsers(Request $request)
    {
        $currentUser = $request->user();
        
        if ($currentUser->isSuperAdmin()) {
            $users = User::all();
        } else if ($currentUser->isAdmin()) {
            // Админ видит всех менеджеров и админов (но не суперадминов)
            $users = User::where('role', '!=', 'superadmin')->get();
        } else {
            // Менеджер видит только менеджеров
            $users = User::where('role', 'manager')->get();
        }

        return response()->json([
            'success' => true,
            'users' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at,
                ];
            })
        ]);
    }

    public function deleteUser(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $currentUser = $request->user();

            // Нельзя удалить себя
            if ($currentUser->id == $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete yourself'
                ], 400);
            }

            // Проверка прав на удаление
            if (!$this->canManageUser($currentUser, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to delete this user'
                ], 403);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function user(Request $request)
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $user = $request->user();
        $token = $user->currentAccessToken();
        $expiresAt = $token->expires_at;
        
        if ($expiresAt && Carbon::parse($expiresAt)->isPast()) {
            $user->currentAccessToken()->delete();
            
            return response()->json([
                'success' => false,
                'message' => 'Token expired',
                'expired' => true
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'permissions' => $this->getUserPermissions($user->role)
            ],
            'token_expires_at' => $expiresAt,
            'token_expires_in' => $expiresAt ? Carbon::parse($expiresAt)->diffInSeconds(now()) : null,
        ]);
    }

    // Вспомогательные методы
    private function getUserPermissions(string $role): array
    {
        $permissions = [
            'superadmin' => [
                'create_users',
                'edit_users',
                'delete_users',
                'create_posts',
                'edit_posts',
                'delete_posts',
                'create_pages',
                'edit_pages',
                'delete_pages',
                'manage_tags',
                'manage_employees',
                'manage_testimonials',
                'manage_partners',
                'manage_menus',
                'manage_settings',
            ],
            'admin' => [
                'create_posts',
                'edit_posts',
                'delete_posts',
                'create_pages',
                'edit_pages',
                'delete_pages',
                'manage_tags',
                'manage_employees',
                'manage_testimonials',
                'manage_partners',
                'manage_menus',
                'manage_settings',
            ],
            'manager' => [
                'create_posts',
                'edit_posts',
                'delete_posts',
                'create_pages',
                'edit_pages',
                'delete_pages',
                'manage_tags',
                'manage_employees',
                'manage_testimonials',
                'manage_partners',
            ],
        ];

        return $permissions[$role] ?? [];
    }

    private function canManageUser(User $currentUser, User $targetUser): bool
    {
        if ($currentUser->isSuperAdmin()) {
            return true; // Суперадмин может управлять всеми
        }

        if ($currentUser->isAdmin()) {
            // Админ может управлять только менеджерами
            return $targetUser->role === 'manager';
        }

        return false; // Менеджер не может управлять пользователями
    }
}