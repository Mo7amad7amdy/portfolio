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

class ProfileController extends Controller
{
    /** Upload fields => [target folder, validation rules]. */
    private const UPLOADS = [
        'photo' => ['photos', ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096']],
        'hero_head' => ['hero', ['nullable', 'image', 'mimes:png,webp', 'max:4096']],
        'hero_body' => ['hero', ['nullable', 'image', 'mimes:png,webp', 'max:4096']],
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
        unset($data['remove_hero_layers']);

        $profile->update($data);

        return back()->with('status', 'Profile saved.');
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
