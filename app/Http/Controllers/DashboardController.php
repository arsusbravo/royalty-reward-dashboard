<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'total_clients'    => Client::count(),
            'enrolled_clients' => Client::where('face_enrolled', true)->count(),
            'total_employees'  => User::where('role', 'employee')->count(),
            'recent_clients'   => Client::latest()
                ->limit(5)
                ->with('creator:id,name')
                ->get()
                ->map(fn($c) => [
                    'id'            => $c->id,
                    'name'          => $c->name,
                    'email'         => $c->email,
                    'face_enrolled' => $c->face_enrolled,
                    'photo_url'     => $c->photo_path ? asset('storage/' . $c->photo_path) : null,
                    'created_at'    => $c->created_at->toDateTimeString(),
                    'creator'       => $c->creator ? ['id' => $c->creator->id, 'name' => $c->creator->name] : null,
                ]),
        ]);
    }
}
