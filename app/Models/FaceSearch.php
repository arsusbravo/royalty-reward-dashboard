<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceSearch extends Model
{
    protected $fillable = ['client_id', 'searched_by', 'matched', 'similarity', 'candidates', 'photo_path'];

    protected function casts(): array
    {
        return ['matched' => 'boolean', 'similarity' => 'float', 'candidates' => 'array'];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function searchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'searched_by');
    }
}
