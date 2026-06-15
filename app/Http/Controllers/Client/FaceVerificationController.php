<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\FaceVerification;
use App\Services\FaceRecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FaceVerificationController extends Controller
{
    public function __construct(
        private FaceRecognitionService $faceService
    ) {}

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'selfie' => ['required', 'image', 'max:10240'],
        ]);

        $client = $request->user();

        if (! $client->photo_path) {
            return response()->json([
                'message' => 'No reference photo on file. Please contact an administrator.',
            ], 422);
        }

        if (! $client->face_enrolled) {
            return response()->json([
                'message' => 'Face not enrolled yet. Please contact an administrator.',
            ], 422);
        }

        $selfiePath = null;

        try {
            // Store the selfie for audit purposes
            $selfiePath = $request->file('selfie')->store('verifications', 'public');

            // Get absolute path of the client's stored reference photo
            $referencePath = Storage::disk('public')->path($client->photo_path);

            $result = $this->faceService->compareFaces(
                $request->file('selfie'),
                $referencePath
            );
        } catch (\RuntimeException $e) {
            Log::error('Face verification service error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Face verification service is currently unavailable. Please try again later.',
            ], 503);
        }

        $matched    = (bool) ($result['is_same_person'] ?? false);
        $similarity = (float) ($result['similarity'] ?? 0.0);

        $verification = FaceVerification::create([
            'client_id'   => $client->id,
            'matched'     => $matched,
            'similarity'  => $similarity,
            'selfie_path' => $selfiePath,
            'ip_address'  => $request->ip(),
        ]);

        return response()->json([
            'matched'     => $matched,
            'similarity'  => $similarity,
            'verified_at' => $verification->created_at->toDateTimeString(),
        ]);
    }
}
