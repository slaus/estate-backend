<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Вход в админку (только для админов и менеджеров)
     */
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
            
            // Проверяем, что пользователь имеет роль (админ, суперадмин, менеджер)
            if ($user->role === null) {
                Auth::logout();
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Please use the site login.'
                ], 403);
            }

            $token = $user->createToken('admin-api-token', ['admin:*'], now()->addHours(1))->plainTextToken;

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                    'permissions' => $this->getUserPermissions($user->role)
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'expires_at' => now()->addHours(1)->toISOString(),
                'message' => 'Успішний вхід в адмінку',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Невірний email або пароль'
        ], 401);
    }

    /**
     * Выход из админки
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }

    /**
     * Создать нового администратора (только для суперадминов)
     */
    public function createAdmin(Request $request)
    {
        $currentUser = $request->user();
        
        if (!$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions to create administrators'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:manager,admin,superadmin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $admin = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Administrator created successfully',
            'data' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
                'created_at' => $admin->created_at,
            ]
        ], 201);
    }

    /**
     * Получить список администраторов (только для суперадминов)
     */

	public function getAdmins(Request $request)
	{
		$currentUser = $request->user();
		
		if (!$currentUser->isSuperAdmin()) {
			return response()->json([
				'success' => false,
				'message' => 'Insufficient permissions'
			], 403);
		}

		// Получаем только пользователей с ролями admin, superadmin, manager
		$admins = User::whereIn('role', ['superadmin', 'admin', 'manager'])
					 ->orderBy('created_at', 'desc')
					 ->paginate($request->get('per_page', 15));

		return response()->json([
			'success' => true,
			'data' => $admins->items(),
			'pagination' => [
				'current_page' => $admins->currentPage(),
				'last_page' => $admins->lastPage(),
				'per_page' => $admins->perPage(),
				'total' => $admins->total(),
			]
		]);
	}

    /**
     * Обновить администратора (только для суперадминов)
     */
    public function updateAdmin(Request $request, $id)
    {
        $currentUser = $request->user();
        
        if (!$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions'
            ], 403);
        }

        $admin = User::whereNotNull('role')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'role' => 'sometimes|in:manager,admin,superadmin',
            'password' => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'email', 'role']);
        
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $admin->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Administrator updated successfully',
            'data' => $admin
        ]);
    }

    /**
     * Удалить администратора (только для суперадминов)
     */
    public function deleteAdmin($id)
    {
        $currentUser = auth()->user();
        
        if (!$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions'
            ], 403);
        }

        $admin = User::whereNotNull('role')->findOrFail($id);

        // Нельзя удалить самого себя
        if ($admin->id === $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete your own account'
            ], 400);
        }

        $admin->delete();

        return response()->json([
            'success' => true,
            'message' => 'Administrator deleted successfully'
        ]);
    }

    /**
     * Получить информацию о текущем администраторе
     */
    public function user(Request $request)
    {
        $user = $request->user();
        
        // Проверяем, что это администратор
        if ($user->role === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized for admin panel'
            ], 403);
        }

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
                'avatar' => $user->avatar,
                'role' => $user->role,
                'permissions' => $this->getUserPermissions($user->role)
            ],
            'token_expires_at' => $expiresAt,
            'token_expires_in' => $expiresAt ? Carbon::parse($expiresAt)->diffInSeconds(now()) : null,
        ]);
    }

    /**
     * Обновить профиль администратора
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        if ($user->role === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        \Log::info('=== UPDATE PROFILE START ===');
		\Log::info('Current user avatar: ' . ($user->avatar ?? 'null'));

		try {
			$data = [];
			
			if ($request->filled('name')) {
				$data['name'] = $request->name;
			}
			
			if ($request->filled('password_current') && $request->filled('password')) {
				if (!Hash::check($request->password_current, $user->password)) {
					return response()->json(['success' => false, 'message' => 'Current password is incorrect'], 422);
				}
				$data['password'] = Hash::make($request->password);
			}
			
			if ($request->hasFile('avatar')) {
				$file = $request->file('avatar');
				
				if ($user->avatar) {
					try {
						$avatarPath = str_replace(['http://estate-backend.test', 'http://estate.test'], '', $user->avatar);
						
						if (strpos($avatarPath, '/storage/') === 0) {
							$oldPath = str_replace('/storage/', '', $avatarPath);
							if (Storage::disk('public')->exists($oldPath)) {
								Storage::disk('public')->delete($oldPath);
								\Log::info('Deleted old avatar from storage: ' . $oldPath);
							}
						}
					} catch (\Exception $e) {
						\Log::error('Error deleting old avatar: ' . $e->getMessage());
					}
				}
				
				$fileName = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
				
				$path = $file->storeAs('avatars', $fileName, 'public');
				
				$data['avatar'] = '/storage/avatars/' . $fileName;
				
				\Log::info('Avatar saved:', [
					'file_name' => $fileName,
					'storage_path' => $path,
					'avatar_path' => $data['avatar'],
					'full_path' => Storage::disk('public')->path($path),
					'file_exists' => Storage::disk('public')->exists($path)
				]);
			}

			\Log::info('Data to update:', $data);
			
			if (!empty($data)) {
				$user->update($data);
				$user->refresh();
				
				\Log::info('User after update - avatar: ' . $user->avatar);
			}

			return response()->json([
				'success' => true,
				'user' => [
					'id' => $user->id,
					'name' => $user->name,
					'email' => $user->email,
					'avatar' => $user->avatar,
					'role' => $user->role,
					'permissions' => $this->getUserPermissions($user->role)
				],
				'message' => 'Profile updated successfully',
			]);

		} catch (\Exception $e) {
			\Log::error('Update error: ' . $e->getMessage());
			\Log::error('Stack trace: ' . $e->getTraceAsString());
			
			return response()->json([
				'success' => false,
				'message' => 'Update failed: ' . $e->getMessage()
			], 500);
		}
    }

    /**
     * Удалить аватар администратора
     */
    public function removeAvatar(Request $request)
    {
        $user = $request->user();
        
        if ($user->role === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
			if ($user->avatar) {
				if (strpos($user->avatar, '/storage/') === 0) {
					$path = str_replace('/storage/', '', $user->avatar);
					if (Storage::disk('public')->exists($path)) {
						Storage::disk('public')->delete($path);
						\Log::info('Removed avatar from storage: ' . $path);
					}
				} elseif (strpos($user->avatar, '/uploads/') === 0) {
					$path = public_path($user->avatar);
					if (file_exists($path)) {
						unlink($path);
						\Log::info('Removed avatar from uploads: ' . $path);
					}
				}
			}
			
			$user->avatar = null;
			$user->save();

			return response()->json([
				'success' => true,
				'user' => [
					'id' => $user->id,
					'name' => $user->name,
					'email' => $user->email,
					'avatar' => null,
					'role' => $user->role,
					'permissions' => $this->getUserPermissions($user->role)
				],
				'message' => 'Avatar removed successfully',
			]);

		} catch (\Exception $e) {
			\Log::error('Failed to remove avatar: ' . $e->getMessage());
			
			return response()->json([
				'success' => false,
				'message' => 'Failed to remove avatar: ' . $e->getMessage()
			], 500);
		}
    }

    /**
     * Получить пользователя по ID (для админов)
     */
    public function getUserById(Request $request, $id)
    {
        $currentUser = $request->user();
        
        if (!$currentUser->isAdmin() && !$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions'
            ], 403);
        }

        $user = User::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'role' => $user->role,
                'created_at' => $user->created_at,
            ]
        ]);
    }

    private function getUserPermissions(string $role): array
    {
        $permissions = [
            'superadmin' => [
                'manage_admins',
                'manage_users',
                'manage_content',
                'manage_settings',
                'manage_menus',
                'view_analytics',
            ],
            'admin' => [
                'manage_users',
                'manage_content',
                'manage_settings',
                'manage_menus',
                'view_analytics',
            ],
            'manager' => [
                'manage_content',
                'view_analytics',
            ],
        ];

        return $permissions[$role] ?? [];
    }
	
	
	//private function canManageUser(User $currentUser, User $targetUser): bool
    //{
    //    if ($currentUser->isSuperAdmin()) {
    //        return true;
    //    }

    //    if ($currentUser->isAdmin()) {
    //        return $targetUser->role === 'manager';
    //    }

    //    return false;
    //}
}
