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
					'avatar' => $user->avatar,
                    'role' => $user->role,
                    'permissions' => $this->getUserPermissions($user->role)
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

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|in:manager,admin,superadmin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role ?? 'manager',
            ];

            $currentUser = $request->user();
            if ($currentUser) {
                if ($currentUser->isSuperAdmin()) {
                    $data['role'] = $request->role ?? 'manager';
                } 
                else if ($currentUser->isAdmin() && (!$request->role || $request->role === 'manager')) {
                    $data['role'] = 'manager';
                }
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
					'avatar' => $user->avatar,
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
            $users = User::where('role', '!=', 'superadmin')->get();
        } else {
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
	
	public function getUserById(Request $request, $id)
    {
        $currentUser = $request->user();
        
        if (!$currentUser->isAdmin() && !$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions'
            ], 403);
        }

        try {
            $user = User::findOrFail($id);

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }

    public function deleteUser(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $currentUser = $request->user();

            if ($currentUser->id == $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete yourself'
                ], 400);
            }

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
				'avatar' => $user->avatar,
                'role' => $user->role,
                'permissions' => $this->getUserPermissions($user->role)
            ],
            'token_expires_at' => $expiresAt,
            'token_expires_in' => $expiresAt ? Carbon::parse($expiresAt)->diffInSeconds(now()) : null,
        ]);
    }
	
	
	public function updateProfile(Request $request)
	{
		$user = $request->user();
		
		\Log::info('=== UPDATE PROFILE START ===');
		\Log::info('Current user avatar: ' . ($user->avatar ?? 'null'));

		try {
			$data = [];
			
			// Имя
			if ($request->filled('name')) {
				$data['name'] = $request->name;
			}
			
			// Пароль
			if ($request->filled('password_current') && $request->filled('password')) {
				if (!Hash::check($request->password_current, $user->password)) {
					return response()->json(['success' => false, 'message' => 'Current password is incorrect'], 422);
				}
				$data['password'] = Hash::make($request->password);
			}
			
			// Аватар
			if ($request->hasFile('avatar')) {
				$file = $request->file('avatar');
				
				// 1. Удаляем старый аватар если он в storage
				if ($user->avatar) {
					try {
						// Убираем полный URL если есть, оставляем только путь
						$avatarPath = str_replace(['http://estate-backend.test', 'http://estate.test'], '', $user->avatar);
						
						if (strpos($avatarPath, '/storage/') === 0) {
							// Удаляем из storage
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
				
				// 2. Сохраняем новый аватар в storage (public disk)
				$fileName = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
				
				// Сохраняем в public disk
				$path = $file->storeAs('avatars', $fileName, 'public');
				
				// ВОЗВРАЩАЕМ ОТНОСИТЕЛЬНЫЙ ПУТЬ, а не полный URL
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
			
			// Обновляем пользователя
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
					'avatar' => $user->avatar, // Будет относительный путь /storage/avatars/...
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

	public function removeAvatar(Request $request)
	{
		$user = $request->user();

		try {
			// Удаляем аватар из storage если он там
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
			
			// Очищаем поле в базе
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
            return true;
        }

        if ($currentUser->isAdmin()) {
            return $targetUser->role === 'manager';
        }

        return false;
    }
}