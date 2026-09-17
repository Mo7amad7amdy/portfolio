<?php

namespace App\Models;

use App\Models\Concerns\Ordered;
use App\Support\SocialPlatforms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    use Ordered;

    protected $attributes = ['is_visible' => true];

    protected $fillable = ['platform', 'url', 'label', 'is_visible', 'sort_order'];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    /** Platform display name, e.g. "Instagram". */
    protected function name(): Attribute
    {
        return Attribute::get(fn () => SocialPlatforms::get($this->platform)['name']);
    }

    /** What visitors read: custom label, or "@username" / host. */
    protected function display(): Attribute
    {
        return Attribute::get(fn () => $this->label ?: SocialPlatforms::label($this->platform, $this->url));
    }

    protected function icon(): Attribute
    {
        return Attribute::get(fn () => SocialPlatforms::get($this->platform)['icon']);
    }
}
