<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ]);

        $user = User::query()
            ->where('email', $data['email'])
            ->where('actif', true)
            ->first();

        if (! $user instanceof User || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Identifiants invalides.',
            ]);
        }

        $token = $user->createToken($data['device_name'] ?? 'api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->utilisateur($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->utilisateur($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function utilisateur(User $user): array
    {
        return [
            'id' => $user->getKey(),
            'nom_complet' => $user->nomComplet(),
            'email' => $user->email,
            'role' => $user->role->value,
            'role_label' => $user->role->label(),
            'exploitation_id' => $user->exploitation_id,
        ];
    }
}
