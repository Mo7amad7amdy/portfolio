<?php

namespace App\Support;

use App\Models\Certification;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Language;
use App\Models\Profile;
use App\Models\Skill;
use App\Models\SocialLink;
use App\Models\Stat;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Everything search engines and social networks read: title, description,
 * share image (auto-generated card or a custom upload) and schema.org data.
 */
class Seo
{
    private const CARD_DIR = 'images/social';

    private const CARD_VERSION = 1; // bump when the card design changes

    public function __construct(private Profile $profile) {}

    public static function for(Profile $profile): self
    {
        return new self($profile);
    }

    public function title(): string
    {
        return $this->profile->seo_title
            ?: Str::limit("{$this->profile->name} — {$this->profile->title}", 70, '');
    }

    public function description(): string
    {
        $text = $this->profile->seo_description
            ?: collect([$this->profile->tagline, $this->profile->paragraphs()[0] ?? null])->filter()->implode(' ');

        return Str::limit(trim(preg_replace('/\s+/', ' ', $text)), 160);
    }

    public function url(): string
    {
        return url('/');
    }

    public function twitter(): ?string
    {
        $h = trim((string) $this->profile->twitter_handle);
        if ($h === '' && ($x = SocialLink::visible()->where('platform', 'x')->value('url'))) {
            $h = basename(parse_url($x, PHP_URL_PATH) ?: '');
        }

        return $h === '' ? null : '@'.ltrim($h, '@');
    }

    /** @return array{url: string, width: int, height: int, type: string, alt: string}|null */
    public function image(): ?array
    {
        $alt = "{$this->profile->name} — {$this->profile->title}";

        if ($this->profile->seo_image && is_file(public_path($this->profile->seo_image))) {
            $size = @getimagesize(public_path($this->profile->seo_image)) ?: [1200, 630, 'mime' => 'image/jpeg'];

            return ['url' => $this->absolute($this->profile->seo_image), 'width' => $size[0], 'height' => $size[1], 'type' => $size['mime'], 'alt' => $alt];
        }

        if ($card = $this->card()) {
            return ['url' => $this->absolute($card), 'width' => SocialCard::WIDTH, 'height' => SocialCard::HEIGHT, 'type' => 'image/png', 'alt' => $alt];
        }

        if ($this->profile->photo) {
            return ['url' => $this->absolute($this->profile->photo), 'width' => 800, 'height' => 1000, 'type' => 'image/jpeg', 'alt' => $alt];
        }

        return null;
    }

    /**
     * Returns the generated share card, (re)building it when the profile, stats or
     * portrait changed. The content hash is in the file name, so Facebook/LinkedIn/X
     * see a new URL and refresh their cached preview.
     */
    public function card(): ?string
    {
        $data = $this->cardData();
        $hash = substr(md5(json_encode($data).self::CARD_VERSION.(is_file($data['portrait'] ?? '') ? filemtime($data['portrait']) : '')), 0, 12);
        $relative = self::CARD_DIR."/card-{$hash}.png";
        $path = public_path($relative);

        if (is_file($path)) {
            return $relative;
        }

        try {
            SocialCard::render($data, $path);
        } catch (Throwable $e) {
            Log::warning('Social card could not be generated: '.$e->getMessage());

            return null;
        }

        foreach (File::glob(public_path(self::CARD_DIR.'/card-*.png')) as $old) {
            if ($old !== $path) {
                File::delete($old);
            }
        }

        return $relative;
    }

    public function schema(): array
    {
        $p = $this->profile;
        $current = Experience::ordered()->first();
        $same = SocialLink::visible()->ordered()->pluck('url')
            ->merge([$p->linkedin_url, $p->github_url])
            ->filter()->unique()->values()->all();

        $person = array_filter([
            '@type' => 'Person',
            '@id' => $this->url().'#person',
            'name' => $p->name,
            'jobTitle' => $p->title,
            'description' => $this->description(),
            'url' => $this->url(),
            'image' => $p->photo ? $this->absolute($p->photo) : null,
            'email' => $p->email ? 'mailto:'.$p->email : null,
            'address' => $p->location ? ['@type' => 'PostalAddress', 'addressLocality' => trim(explode(',', $p->location)[0]), 'addressCountry' => trim(Str::afterLast($p->location, ','))] : null,
            'sameAs' => $same ?: null,
            'knowsAbout' => Skill::ordered()->where('level', '>=', 70)->pluck('name')->all() ?: null,
            'knowsLanguage' => Language::ordered()->pluck('name')->all() ?: null,
            'worksFor' => $current?->is_current ? ['@type' => 'Organization', 'name' => $current->company] : null,
            'alumniOf' => Education::ordered()->get()->map(fn ($e) => ['@type' => 'EducationalOrganization', 'name' => $e->institution])->all() ?: null,
            'hasCredential' => Certification::ordered()->get()->map(fn ($c) => array_filter([
                '@type' => 'EducationalOccupationalCredential', 'name' => $c->title,
                'recognizedBy' => $c->issuer ? ['@type' => 'Organization', 'name' => $c->issuer] : null,
            ]))->all() ?: null,
        ]);

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'ProfilePage',
                    '@id' => $this->url().'#page',
                    'url' => $this->url(),
                    'name' => $this->title(),
                    'description' => $this->description(),
                    'dateModified' => optional($p->updated_at)->toAtomString(),
                    'mainEntity' => ['@id' => $this->url().'#person'],
                ],
                $person,
                [
                    '@type' => 'WebSite',
                    '@id' => $this->url().'#website',
                    'url' => $this->url(),
                    'name' => $p->name,
                    'inLanguage' => 'en',
                ],
            ],
        ];
    }

    private function cardData(): array
    {
        $p = $this->profile;
        $portrait = collect([$p->hero_portrait, $p->photo])
            ->filter(fn ($f) => $f && is_file(public_path($f)))
            ->map(fn ($f) => public_path($f))
            ->first();

        return [
            'name' => $p->name,
            'title' => $p->title,
            'tagline' => $p->tagline,
            'location' => $p->location,
            'domain' => $this->publicDomain(),
            'portrait' => $portrait,
            'stats' => Stat::ordered()->take(3)->get()->map(fn ($s) => [$s->value, $s->label])->all(),
        ];
    }

    /** The site's domain, but only when it's a real public one (not localhost / *.test). */
    private function publicDomain(): ?string
    {
        $host = parse_url(config('app.url') ?: url('/'), PHP_URL_HOST) ?: '';
        $host = preg_replace('/^www\./', '', $host);

        if ($host === '' || ! str_contains($host, '.') || filter_var($host, FILTER_VALIDATE_IP)
            || preg_match('/\.(test|local|localhost|example|invalid)$/', $host)) {
            return null;
        }

        return $host;
    }

    private function absolute(string $path): string
    {
        return str_starts_with($path, 'http') ? $path : asset($path);
    }
}
