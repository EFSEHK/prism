<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\LoginIdentifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|max:255',
                'password' => 'required|string',
            ]);

            $rawIdentifier = trim((string) $request->input('email'));
            $password = (string) $request->input('password');
            // Trim password edges only — never alter internal spaces (passwords may contain them).
            $password = trim($password);

            $email = LoginIdentifier::resolveEmail($rawIdentifier);
            $user = User::where('email', $email)->first();

            if (! $user && str_contains($rawIdentifier, '@')) {
                $user = User::where('email', strtolower($rawIdentifier))->first();
            }

            $passwordOk = $user && Hash::check($password, $user->password);

            if (! $user || ! $passwordOk) {
                $this->logLoginFailure($request, $rawIdentifier, $email, $user !== null, $passwordOk);

                event(new \Illuminate\Auth\Events\Failed('web', $user, [
                    'email' => $rawIdentifier,
                    'password' => '******',
                ]));

                return response()->json([
                    'message' => 'Invalid login details',
                ], 401);
            }

            // Keep existing tokens so web and mobile sessions can coexist.
            // Logout only deletes the current access token.
            $tokenName = $request->input('device_name', 'auth_token');
            $token = $user->createToken(is_string($tokenName) && $tokenName !== '' ? $tokenName : 'auth_token');

            event(new \Illuminate\Auth\Events\Login('web', $user, false));

            $user->load(['roles:id,name']);
            $user->setRelation('permissions', $user->getAllPermissions());

            return response()->json([
                'access_token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'user' => $user,
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation error during login: ' . json_encode($e->errors()));
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error during login: ' . $e->getMessage());
            return response()->json([
                'message' => 'Login failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            event(new \Illuminate\Auth\Events\Logout('web', $user));
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Successfully logged out'
            ]);
        } catch (\Exception $e) {
            Log::error('Error during logout: ' . $e->getMessage());
            return response()->json([
                'message' => 'Logout failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Structured failure log so intermittent 401s can be diagnosed without guessing.
     * Never logs the password or full secret material.
     */
    private function logLoginFailure(
        Request $request,
        string $rawIdentifier,
        string $resolvedEmail,
        bool $userFound,
        bool $passwordOk,
    ): void {
        Log::warning('login.failed', [
            'reason' => ! $userFound ? 'user_not_found' : 'password_mismatch',
            'identifier_has_at' => str_contains($rawIdentifier, '@'),
            'identifier_length' => strlen($rawIdentifier),
            'resolved_email' => $resolvedEmail,
            'user_found' => $userFound,
            'password_ok' => $passwordOk,
            'users_table_count' => User::query()->count(),
            'db_connection' => config('database.default'),
            'db_database' => DB::connection()->getDatabaseName(),
            'app_env' => config('app.env'),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 180),
        ]);
    }
}
