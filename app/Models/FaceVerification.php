<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceVerification extends Model
{
    protected $fillable = [
        'client_id',
        'matched',
        'similarity',
        'selfie_path',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'matched'    => 'boolean',
            'similarity' => 'float',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
