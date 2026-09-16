<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'name', 'title', 'tagline', 'summary', 'email', 'phone', 'location',
        'linkedin_url', 'github_url', 'photo', 'hero_head', 'hero_body', 'cv_file', 'open_to_work',
    ];

    protected function casts(): array
    {
        return ['open_to_work' => 'boolean'];
    }

    /** The portfolio has exactly one profile row. */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'name' => config('app.name'),
            'title' => 'Developer',
        ]);
    }

    public function firstName(): string
    {
        return explode(' ', trim($this->name))[0];
    }

    public function hasHeroLayers(): bool
    {
        return filled($this->hero_head) && filled($this->hero_body);
    }

    /** Summary split into paragraphs. @return list<string> */
    public function paragraphs(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R{2,}/', (string) $this->summary))));
    }
}
