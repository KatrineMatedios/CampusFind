<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Claim extends Model
{
    protected $fillable = [
        'item_id',
        'user_id',
        'identifiers',
        'proof_photo',
        'pickup_location',
        'pickup_time',
        'qr_code',
        'status',
    ];

    protected $appends = ['proof_photo_url'];

    protected function casts(): array
    {
        return [
            'pickup_time' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Claim $claim) {
            $claim->qr_code ??= 'CF-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
        });
    }

    public function getProofPhotoUrlAttribute(): ?string
    {
        return $this->proof_photo ? Storage::disk('public')->url($this->proof_photo) : null;
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function claimant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
