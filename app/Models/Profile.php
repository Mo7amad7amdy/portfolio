<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    /** Eye/head positions (0–1, image space) for the bundled portrait. */
    public const DEFAULT_PORTRAIT_META = [
        'version' => 1,
        'focus' => 0.36,
        'eyes' => [
            ['x' => 0.3547, 'y' => 0.2539, 'rx' => 0.0446, 'ry' => 0.0136],
            ['x' => 0.5330, 'y' => 0.2596, 'rx' => 0.0463, 'ry' => 0.0136],
        ],
        'head' => ['x' => 0.4501, 'y' => 0.2710, 'rx' => 0.2674, 'ry' => 0.2996],
    ];

    protected $fillable = [
        'name', 'title', 'tagline', 'summary', 'email', 'phone', 'location',
        'linkedin_url', 'github_url', 'photo', 'hero_head', 'hero_body',
        'hero_portrait', 'hero_depth', 'hero_closed', 'hero_meta', 'cv_file', 'open_to_work',
    ];

    protected function casts(): array
    {
        return [
            'open_to_work' => 'boolean',
            'hero_meta' => 'array',
        ];
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

    /** WebGL living portrait needs the cut-out, its depth map and both eye positions. */
    public function hasLivingPortrait(): bool
    {
        return filled($this->hero_portrait)
            && filled($this->hero_depth)
            && count($this->hero_meta['eyes'] ?? []) === 2;
    }

    /** Hero rendering mode, best first: living → layers → photo. */
    public function heroMode(): string
    {
        return match (true) {
            $this->hasLivingPortrait() => 'living',
            $this->hasHeroLayers() => 'layers',
            default => 'photo',
        };
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
