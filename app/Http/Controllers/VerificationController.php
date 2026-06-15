<?php

namespace App\Http\Controllers;

use App\Models\FaceVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 15), 50);

        $verifications = FaceVerification::with('client:id,name,photo_path')
            ->latest()
            ->paginate($perPage);

        $verifications->getCollection()->transform(fn($v) => [
            'id'          => $v->id,
            'client'      => $v->client ? [
                'id'        => $v->client->id,
                'name'      => $v->client->name,
                'photo_url' => $v->client->photo_path
                    ? asset('storage/' . $v->client->photo_path)
                    : null,
            ] : null,
            'matched'     => $v->matched,
            'similarity'  => $v->similarity,
            'verified_at' => $v->created_at->toDateTimeString(),
        ]);

        return response()->json($verifications);
    }
}
