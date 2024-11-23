<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;

class SocialAuthController extends Controller
{
    public function callback(Request $request, $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();

            // Check if user exists by provider ID or email
            $user = User::where("{$provider}_id", $socialUser->getId())
                        ->orWhere('email', $socialUser->getEmail())
                        ->first();

            if ($user) {
                // Update provider ID if user already exists
                $user->update(["{$provider}_id" => $socialUser->getId()]);
            } else {
                // Create a new user
                $user = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname(),
                    'email' => $socialUser->getEmail(),
                    "{$provider}_id" => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);
            }

            // Generate a personal access token for API usage
            $token = $user->createToken('API Token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'user' => $user,
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
