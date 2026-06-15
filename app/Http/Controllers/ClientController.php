<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Services\FaceRecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function __construct(
        private FaceRecognitionService $faceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $clients = Client::query()
            ->with('creator:id,name')
            ->when($request->search, fn($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
            )
            ->latest()
            ->paginate(20);

        $clients->getCollection()->transform(fn($client) => $this->formatClient($client));

        return response()->json($clients);
    }

    public function show(Client $client): JsonResponse
    {
        $client->load('creator:id,name');

        return response()->json($this->formatClient($client));
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $data               = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['face_enrolled'] = false;
        $data['face_api_id']   = null;

        if (empty($data['password'])) {
            unset($data['password']);
        }
        unset($data['password_confirmation']);

        if ($request->hasFile('photo')) {
            $file              = $request->file('photo');
            $data['photo_path'] = $file->store('clients/photos', 'public');

            try {
                $result              = $this->faceService->registerFace($file, $data['name']);
                $data['face_enrolled'] = true;
                $data['face_api_id']   = $result['id'] ?? null;
            } catch (\RuntimeException $e) {
                Log::warning('Face enrollment failed on create: ' . $e->getMessage());
            }
        }

        $client = Client::create($data);

        return response()->json($this->formatClient($client->load('creator:id,name')), 201);
    }

    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }
        unset($data['password_confirmation']);

        if ($request->hasFile('photo')) {
            if ($client->photo_path) {
                Storage::disk('public')->delete($client->photo_path);
            }

            $file              = $request->file('photo');
            $data['photo_path'] = $file->store('clients/photos', 'public');

            // Delete old face registration if it exists
            if ($client->face_api_id) {
                try {
                    $this->faceService->deleteFace($client->face_api_id);
                } catch (\RuntimeException $e) {
                    Log::warning('Could not delete old face registration: ' . $e->getMessage());
                }
            }

            try {
                $result              = $this->faceService->registerFace($file, $data['name'] ?? $client->name);
                $data['face_enrolled'] = true;
                $data['face_api_id']   = $result['id'] ?? null;
            } catch (\RuntimeException $e) {
                Log::warning('Face re-enrollment failed: ' . $e->getMessage());
                $data['face_enrolled'] = false;
                $data['face_api_id']   = null;
            }
        }

        $client->update($data);

        return response()->json($this->formatClient($client->fresh()->load('creator:id,name')));
    }

    public function destroy(Client $client): JsonResponse
    {
        if ($client->face_api_id) {
            try {
                $this->faceService->deleteFace($client->face_api_id);
            } catch (\RuntimeException $e) {
                Log::warning('Could not delete face from API on client delete: ' . $e->getMessage());
            }
        }

        if ($client->photo_path) {
            Storage::disk('public')->delete($client->photo_path);
        }

        $client->delete();

        return response()->json(['message' => 'Client deleted.']);
    }

    public function updatePhoto(Request $request, Client $client): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:10240'],
        ]);

        if ($client->photo_path) {
            Storage::disk('public')->delete($client->photo_path);
        }

        $file      = $request->file('photo');
        $photoPath = $file->store('clients/photos', 'public');

        $faceEnrolled = false;
        $faceApiId    = null;

        if ($client->face_api_id) {
            try {
                $this->faceService->deleteFace($client->face_api_id);
            } catch (\RuntimeException $e) {
                Log::warning('Could not delete old face on photo update: ' . $e->getMessage());
            }
        }

        try {
            $result       = $this->faceService->registerFace($file, $client->name);
            $faceEnrolled = true;
            $faceApiId    = $result['id'] ?? null;
        } catch (\RuntimeException $e) {
            Log::warning('Face enrollment failed on photo update: ' . $e->getMessage());
        }

        $client->update([
            'photo_path'    => $photoPath,
            'face_enrolled' => $faceEnrolled,
            'face_api_id'   => $faceApiId,
        ]);

        return response()->json($this->formatClient($client->fresh()->load('creator:id,name')));
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
            'photo_path'    => $client->photo_path,
            'photo_url'     => $client->photo_path ? asset('storage/' . $client->photo_path) : null,
            'face_enrolled' => $client->face_enrolled,
            'face_api_id'   => $client->face_api_id,
            'created_at'    => $client->created_at->toDateTimeString(),
            'updated_at'    => $client->updated_at->toDateTimeString(),
            'creator'       => $client->creator ? ['id' => $client->creator->id, 'name' => $client->creator->name] : null,
        ];
    }
}
