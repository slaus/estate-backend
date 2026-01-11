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
			$token = $user->createToken('api-token')->plainTextToken;

			return response()->json([
				'user' => [
					'id' => $user->id,
					'name' => $user->name,
					'email' => $user->email
				],
				'token' => $token,
				'token_type' => 'Bearer',
				'message' => 'Успішний вхід',
			]);
		}

		return response()->json([
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

        return response()->json([
            'success' => true,
            'user' => new UserResource($request->user()),
        ]);
    }
}