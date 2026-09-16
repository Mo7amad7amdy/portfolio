<?php

namespace App\Models;

use App\Models\Concerns\SplitsTech;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use SplitsTech;

    protected $fillable = ['title', 'client', 'summary', 'tech', 'url', 'is_featured', 'sort_order'];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean'];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('is_featured')->orderBy('sort_order')->orderBy('id');
    }
}
