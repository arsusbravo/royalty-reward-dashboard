<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClientAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $client = Client::where('email', $request->email)->first();

        if (! $client || ! $client->password || ! Hash::check($request->password, $client->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        // Revoke existing tokens to keep only one active session
        $client->tokens()->delete();

        $token = $client->createToken('client-app')->plainTextToken;

        return response()->json([
            'token'  => $token,
            'client' => $this->formatClient($client),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json($this->formatClient($request->user()));
    }

    private function formatClient(Client $client): array
    {
        return [
            'id'            => $client->id,
            'name'          => $client->name,
            'email'         => $client->email,
            'phone'         => $client->phone,
            'date_of_birth' => $client->date_of_birth?->format('Y-m-d'),
            'address'       => $client->address,
            'notes'         => $client->notes,
            'photo_url'     => $client->photo_path ? asset('storage/' . $client->photo_path) : null,
            'face_enrolled' => $client->face_enrolled,
        ];
    }
}
