@extends('admin.layout')

@section('title', 'Profile & Photo')

@section('content')
<form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="stack">
    @csrf
    @method('PUT')

    <section class="card form-card">
        <h2>Basic info</h2>
        <div class="form-grid">
            @foreach ([
                'name' => ['Full name', 'text'],
                'title' => ['Job title', 'text'],
                'email' => ['Public email', 'email'],
                'phone' => ['Phone / WhatsApp', 'text'],
                'location' => ['Location', 'text'],
                'linkedin_url' => ['LinkedIn URL', 'url'],
                'github_url' => ['GitHub URL', 'url'],
            ] as $field => [$label, $type])
                <label class="field">
                    <span>{{ $label }}</span>
                    <input type="{{ $type }}" name="{{ $field }}" value="{{ old($field, $profile->{$field}) }}">
                    @error($field)<small class="error">{{ $message }}</small>@enderror
                </label>
            @endforeach
            <label class="check field-center">
                <input type="hidden" name="open_to_work" value="0">
                <input type="checkbox" name="open_to_work" value="1" @checked(old('open_to_work', $profile->open_to_work))>
                Show “Available for new projects” badge
            </label>
            <label class="field wide">
                <span>Tagline <small>(one sentence under your title)</small></span>
                <input type="text" name="tagline" value="{{ old('tagline', $profile->tagline) }}" maxlength="255">
                @error('tagline')<small class="error">{{ $message }}</small>@enderror
            </label>
            <label class="field wide">
                <span>About / summary <small>(blank line = new paragraph)</small></span>
                <textarea name="summary" rows="8">{{ old('summary', $profile->summary) }}</textarea>
                @error('summary')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="card form-card">
        <h2>Photo &amp; CV</h2>
        <div class="upload-grid">
            <div class="upload">
                <img id="prev-photo" src="{{ $profile->photo ? asset($profile->photo) : '' }}" alt="" @if(!$profile->photo) hidden @endif>
                <label class="field">
                    <span>Profile photo <small>(JPG/PNG/WebP, max 4 MB)</small></span>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-preview="prev-photo">
                    @error('photo')<small class="error">{{ $message }}</small>@enderror
                </label>
                <p class="hint">Used in the About section and as the hero photo when no cut-out layers are set.</p>
            </div>
            <div class="upload">
                <div class="file-box">📄 {{ $profile->cv_file ? basename($profile->cv_file) : 'No CV uploaded' }}</div>
                <label class="field">
                    <span>CV (PDF, max 8 MB)</span>
                    <input type="file" name="cv_file" accept="application/pdf">
                    @error('cv_file')<small class="error">{{ $message }}</small>@enderror
                </label>
                @if ($profile->cv_file)
                    <a href="{{ asset($profile->cv_file) }}" target="_blank" class="hint">Open current CV ↗</a>
                @endif
            </div>
        </div>
    </section>

    <section class="card form-card">
        <h2>Hero “face follows the mouse” layers</h2>
        <p class="hint">
            Two transparent PNG/WebP images of the <strong>same size</strong>: the <strong>head</strong> only, and the <strong>body</strong>
            (with the face area filled in). The head turns toward the cursor on top of the body.
            Without them, the hero tilts your profile photo instead.
        </p>
        <div class="upload-grid">
            <div class="upload checker">
                <img id="prev-head" src="{{ $profile->hero_head ? asset($profile->hero_head) : '' }}" alt="" @if(!$profile->hero_head) hidden @endif>
                <label class="field">
                    <span>Head layer</span>
                    <input type="file" name="hero_head" accept="image/png,image/webp" data-preview="prev-head">
                    @error('hero_head')<small class="error">{{ $message }}</small>@enderror
                </label>
            </div>
            <div class="upload checker">
                <img id="prev-body" src="{{ $profile->hero_body ? asset($profile->hero_body) : '' }}" alt="" @if(!$profile->hero_body) hidden @endif>
                <label class="field">
                    <span>Body layer</span>
                    <input type="file" name="hero_body" accept="image/png,image/webp" data-preview="prev-body">
                    @error('hero_body')<small class="error">{{ $message }}</small>@enderror
                </label>
            </div>
        </div>
        @if ($profile->hasHeroLayers())
            <label class="check"><input type="checkbox" name="remove_hero_layers" value="1"> Remove layers and use the tilting photo instead</label>
        @endif
    </section>

    <div class="form-actions sticky">
        <button class="btn btn-primary" type="submit">Save profile</button>
    </div>
</form>
@endsection
