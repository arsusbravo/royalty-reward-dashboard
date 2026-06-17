<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\FaceSearch;
use App\Services\FaceRecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FaceSearchController extends Controller
{
    public function __construct(
        private FaceRecognitionService $faceService
    ) {}

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:10240'],
        ]);

        try {
            $results = $this->faceService->searchFaces($request->file('photo'), topK: 5);
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'HTTP 422')) {
                return response()->json([
                    'code'    => 'no_face_detected',
                    'message' => 'No valid face detected in the photo.',
                ], 422);
            }

            Log::error('Face search service error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Face recognition service is currently unavailable. Please try again later.',
            ], 503);
        }

        $threshold = (float) config('services.face_api.match_threshold', 0.5);

        // Resolve each ranked candidate's face_api_id to a Client, dropping any
        // that don't map to a known client, then keep the API's similarity order.
        $candidates = collect($results)
            ->map(fn($result) => [
                'client'     => Client::where('face_api_id', $result['id'] ?? null)->with('creator:id,name')->first(),
                'similarity' => (float) ($result['similarity'] ?? 0),
            ])
            ->filter(fn($c) => $c['client'] !== null)
            ->values();

        $matched = $candidates->isNotEmpty() && $candidates->first()['similarity'] >= $threshold;

        FaceSearch::create([
            'client_id'   => $candidates->first()['client']->id ?? null,
            'searched_by' => $request->user()->id,
            'matched'     => $matched,
            'similarity'  => $candidates->first()['similarity'] ?? null,
            'candidates'  => $candidates->map(fn($c) => [
                'client_id'  => $c['client']->id,
                'name'       => $c['client']->name,
                'similarity' => $c['similarity'],
            ])->all(),
            'photo_path'  => null,
        ]);

        return response()->json([
            'matched'     => $matched,
            'threshold'   => $threshold,
            'searched_at' => now()->toDateTimeString(),
            'candidates'  => $candidates->map(fn($c) => [
                'client'     => $this->formatClient($c['client']),
                'similarity' => $c['similarity'],
            ])->all(),
        ]);
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
            'creator'       => $client->creator ? ['id' => $client->creator->id, 'name' => $client->creator->name] : null,
        ];
    }
}
