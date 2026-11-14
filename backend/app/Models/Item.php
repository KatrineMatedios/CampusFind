<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Item extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'category_id',
        'date_reported',
        'location',
        'description',
        'photo',
        'status',
    ];

    protected $appends = ['photo_url'];

    protected function casts(): array
    {
        return [
            'date_reported' => 'date:Y-m-d',
        ];
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? Storage::disk('public')->url($this->photo) : null;
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($filters['category'] ?? null, function (Builder $q, string $category) {
                $q->whereHas('category', fn (Builder $c) => $c->where('id', $category)->orWhere('slug', $category));
            })
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $like = '%'.trim($search).'%';
                $q->where(function (Builder $inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('location', 'like', $like);
                });
            });
    }
}
