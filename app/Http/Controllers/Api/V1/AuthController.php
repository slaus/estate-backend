<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

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
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();
            
            // Создаем токен с явным указанием времени жизни (1 час)
            $token = $user->createToken('api-token', ['*'], now()->addHours(1))->plainTextToken;

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 3600, // 1 час
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'user' => new UserResource($user),
                'token' => $token,
                'message' => 'Користувача успішно зареєстровано',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            if ($request->user()) {
                $request->user()->currentAccessToken()->delete();
            }
            
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'success' => true,
                'message' => 'Успішний вихід',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout error'
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

        // Проверяем время жизни токена
        $token = $request->user()->currentAccessToken();
        $expiresAt = $token->expires_at;
        $isExpired = $expiresAt && Carbon::parse($expiresAt)->isPast();
        
        if ($isExpired) {
            // Токен истек - удаляем его
            $request->user()->currentAccessToken()->delete();
            
            return response()->json([
                'success' => false,
                'message' => 'Token expired',
                'expired' => true
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email
            ],
            'token_expires_at' => $expiresAt,
            'token_expires_in' => $expiresAt ? Carbon::parse($expiresAt)->diffInSeconds(now()) : null,
        ]);
    }
}