<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class UsersController extends Controller
{
    /**
     * Получить список всех пользователей (для админов)
     */
    public function index(Request $request)
	{
		$currentUser = $request->user();
		
		if (!$currentUser->isAdmin() && !$currentUser->isSuperAdmin()) {
			return response()->json([
				'success' => false,
				'message' => 'Insufficient permissions'
			], 403);
		}

		// Получаем только обычных пользователей (без роли)
		$users = User::whereNull('role')
					 ->orderBy('created_at', 'desc')
					 ->paginate($request->get('per_page', 15));

		return response()->json([
			'success' => true,
			'data' => $users->items(),
			'pagination' => [
				'current_page' => $users->currentPage(),
				'last_page' => $users->lastPage(),
				'per_page' => $users->perPage(),
				'total' => $users->total(),
			]
		]);
	}

    /**
     * Получить информацию о пользователе
     */
    public function show($id)
    {
        $user = User::whereNull('role')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }

    /**
     * Создать нового пользователя (публичная регистрация)
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'agree_terms' => 'required|accepted',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            // role = null для обычных пользователей
            'role' => null,
        ]);

        // Автоматический вход после регистрации
        $token = $user->createToken('site-token', ['user:profile'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Обновить пользователя (админ)
     */
    public function update(Request $request, $id)
    {
        $user = User::whereNull('role')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only(['name', 'email', 'phone', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Удалить пользователя (админ)
     */
    public function destroy($id)
    {
        $user = User::whereNull('role')->findOrFail($id);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Получить профиль текущего пользователя (личный кабинет)
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        
        // Проверяем, что это обычный пользователь (не админ)
        if ($user->role !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. This endpoint is for regular users only.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'created_at' => $user->created_at,
                'orders_count' => $user->orders()->count(), // если есть заказы
                'last_login' => $user->last_login_at,
            ]
        ]);
    }

    /**
     * Обновить профиль пользователя (личный кабинет)
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        if ($user->role !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'current_password' => 'required_with:password',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'email', 'phone']);

        // Смена пароля
        if ($request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 422);
            }
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    }
}