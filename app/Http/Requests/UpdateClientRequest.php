<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientId = $this->route('client')?->id;

        return [
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['nullable', 'email', "unique:clients,email,{$clientId}"],
            'phone'         => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'address'       => ['nullable', 'string'],
            'notes'         => ['nullable', 'string'],
            'photo'         => ['nullable', 'image', 'max:10240'],
            'password'      => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
