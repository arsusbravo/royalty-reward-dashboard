<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Client $client): JsonResponse
    {
        $payments = $client->payments()
            ->with('recordedBy:id,name')
            ->latest()
            ->paginate(10);

        $payments->getCollection()->transform(fn($p) => $this->formatPayment($p));

        return response()->json($payments);
    }

    public function store(Request $request, Client $client): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes'  => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = Payment::create([
            'client_id'   => $client->id,
            'recorded_by' => $request->user()->id,
            'amount'      => $data['amount'],
            'notes'       => $data['notes'] ?? null,
        ]);

        return response()->json($this->formatPayment($payment->load('recordedBy:id,name')), 201);
    }

    private function formatPayment(Payment $payment): array
    {
        return [
            'id'          => $payment->id,
            'amount'      => (float) $payment->amount,
            'notes'       => $payment->notes,
            'recorded_by' => $payment->recordedBy ? ['id' => $payment->recordedBy->id, 'name' => $payment->recordedBy->name] : null,
            'created_at'  => $payment->created_at->toDateTimeString(),
        ];
    }
}
