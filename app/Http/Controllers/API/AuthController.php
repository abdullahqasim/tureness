<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // revoke existing tokens if needed
        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        $data = (new UserResource($user))->resolve();
        $data['token'] = $token;

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Logged in',
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()?->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user()),
        ]);
    }

    public function register(\App\Http\Requests\RegisterRequest $request)
    {
        $data = $request->only(['name', 'email', 'phone_number', 'gender']);
        $data['password'] = $request->password; // will be hashed by the model cast

        $user = User::create($data);

        // create token
        $token = $user->createToken('api-token')->plainTextToken;

        $payload = (new UserResource($user))->resolve();
        $payload['token'] = $token;

        return response()->json([
            'success' => true,
            'data' => $payload,
            'message' => 'Registered',
        ], 201);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? response()->json(['success' => true, 'message' => __($status)])
                    : response()->json(['success' => false, 'message' => __($status)], 400);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, $password) {
                $user->forceFill([
                    'password' => $password
                ])->save();

                // Revoke all tokens on password reset
                $user->tokens()->delete();
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            $user = User::where('email', $request->email)->first();
            $token = $user->createToken('api-token')->plainTextToken;

            $data = (new UserResource($user))->resolve();
            $data['token'] = $token;

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Password reset successful',
            ]);
        }

        return response()->json(['success' => false, 'message' => __($status)], 400);
    }
}
