<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visit extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_new' => 'boolean',
            'is_bot' => 'boolean',
            'is_human' => 'boolean',
            'imported' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(VisitEvent::class);
    }

    public function scopeHumans(Builder $query): Builder
    {
        return $query->where('is_bot', false);
    }

    public function place(): string
    {
        return collect([$this->city, $this->country ?: $this->country_code])->filter()->implode(', ') ?: 'Unknown';
    }
}
