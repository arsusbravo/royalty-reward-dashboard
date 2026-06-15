<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['nullable', 'email', 'unique:clients,email'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'address'       => ['nullable', 'string'],
            'notes'         => ['nullable', 'string'],
            'photo'         => ['nullable', 'image', 'max:10240'],
            'password'      => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
