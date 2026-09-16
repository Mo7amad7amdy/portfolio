<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /** Upload fields => [target folder, validation rules]. */
    private const UPLOADS = [
        'photo' => ['photos', ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096']],
        'hero_head' => ['hero', ['nullable', 'image', 'mimes:png,webp', 'max:4096']],
        'hero_body' => ['hero', ['nullable', 'image', 'mimes:png,webp', 'max:4096']],
        'hero_portrait' => ['portrait', ['nullable', 'image', 'mimes:png,webp', 'max:6144']],
        'hero_depth' => ['portrait', ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096']],
        'cv_file' => ['cv', ['nullable', 'file', 'mimes:pdf', 'max:8192']],
    ];

    public function edit(): View
    {
        return view('admin.profile', ['profile' => Profile::current()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $profile = Profile::current();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:120'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'remove_hero_layers' => ['nullable', 'boolean'],
            'remove_living_portrait' => ['nullable', 'boolean'],
            'hero_meta' => ['nullable', 'json'],
            'portrait_focus' => ['nullable', 'numeric', 'between:0,1'],
        ] + array_map(fn (array $cfg) => $cfg[1], self::UPLOADS));

        $data['open_to_work'] = $request->boolean('open_to_work');

        foreach (self::UPLOADS as $field => [$folder]) {
            unset($data[$field]);
            if ($request->hasFile($field)) {
                $this->deleteUpload($profile->{$field});
                $data[$field] = $this->store($request->file($field), $folder);
            }
        }

        // Removing the layers makes the hero fall back to the tilting photo.
        if ($request->boolean('remove_hero_layers')) {
            $this->deleteUpload($profile->hero_head);
            $this->deleteUpload($profile->hero_body);
            $data['hero_head'] = $data['hero_body'] = null;
        }
        if ($request->boolean('remove_living_portrait')) {
            $this->deleteUpload($profile->hero_portrait);
            $this->deleteUpload($profile->hero_depth);
            $data['hero_portrait'] = $data['hero_depth'] = $data['hero_meta'] = null;
        } elseif (filled($request->input('hero_meta'))) {
            $data['hero_meta'] = $this->portraitMeta($request->input('hero_meta'), $request->input('portrait_focus'));
        } else {
            unset($data['hero_meta']);
        }
        unset($data['remove_hero_layers'], $data['remove_living_portrait'], $data['portrait_focus']);

        $profile->update($data);

        return back()->with('status', 'Profile saved.');
    }

    /**
     * Normalises the eye picker's JSON: two eyes and an optional head ellipse,
     * every value clamped to the 0–1 image space.
     */
    private function portraitMeta(string $json, mixed $focus): array
    {
        $meta = json_decode($json, true);
        $eyes = $meta['eyes'] ?? null;

        if (! is_array($eyes) || count($eyes) !== 2) {
            throw ValidationException::withMessages(['hero_meta' => 'Mark both eyes on the portrait preview.']);
        }

        $box = function (mixed $e, array $default): array {
            $e = is_array($e) ? $e : [];
            $out = [];
            foreach ($default as $key => $fallback) {
                $v = $e[$key] ?? $fallback;
                $out[$key] = round(min(1, max(0, is_numeric($v) ? (float) $v : $fallback)), 4);
            }

            return $out;
        };

        $eyeDefault = ['x' => 0.5, 'y' => 0.3, 'rx' => 0.045, 'ry' => 0.014];
        $eyes = array_map(fn ($e) => $box($e, $eyeDefault), array_values($eyes));
        usort($eyes, fn ($a, $b) => $a['x'] <=> $b['x']);

        // Head ellipse defaults to an area around the eyes.
        $midX = ($eyes[0]['x'] + $eyes[1]['x']) / 2;
        $span = max(0.05, $eyes[1]['x'] - $eyes[0]['x']);
        $head = $box($meta['head'] ?? null, [
            'x' => $midX, 'y' => $eyes[0]['y'] + $span * 0.1, 'rx' => $span * 1.5, 'ry' => $span * 1.7,
        ]);

        return [
            'version' => 1,
            'focus' => is_numeric($focus) ? round((float) $focus, 3) : (float) ($meta['focus'] ?? 0.36),
            'eyes' => $eyes,
            'head' => $head,
        ];
    }

    private function store(UploadedFile $file, string $folder): string
    {
        $name = Str::uuid().'.'.($file->guessExtension() ?: $file->getClientOriginalExtension());
        $file->move(public_path("uploads/{$folder}"), $name);

        return "uploads/{$folder}/{$name}";
    }

    /** Only files uploaded through the dashboard are deleted — never the bundled defaults. */
    private function deleteUpload(?string $path): void
    {
        if ($path && str_starts_with($path, 'uploads/')) {
            File::delete(public_path($path));
        }
    }
}
