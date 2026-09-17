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
            <p class="hint wide">Instagram, LinkedIn, GitHub and your other accounts are managed in <a href="{{ route('admin.socials.index') }}">Social links</a>.</p>
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

    @php $shareImage = $seo->image(); @endphp
    <section class="card form-card">
        <h2>SEO &amp; social sharing</h2>
        <p class="hint">What Google shows in search results, and the preview card people see when your link is shared on
            LinkedIn, WhatsApp, Facebook, X, Telegram or Slack. Leave fields empty to use your name, title and tagline.</p>

        <div class="seo-grid">
            <div class="form-grid one">
                <label class="field">
                    <span>Search title <small data-count-for="seo_title">0 / 60</small></span>
                    <input type="text" name="seo_title" maxlength="70" value="{{ old('seo_title', $profile->seo_title) }}" placeholder="{{ $profile->name }} — {{ $profile->title }}" data-count="60">
                    @error('seo_title')<small class="error">{{ $message }}</small>@enderror
                </label>
                <label class="field">
                    <span>Search description <small data-count-for="seo_description">0 / 160</small></span>
                    <textarea name="seo_description" rows="3" maxlength="170" placeholder="{{ $seo->description() }}" data-count="160">{{ old('seo_description', $profile->seo_description) }}</textarea>
                    @error('seo_description')<small class="error">{{ $message }}</small>@enderror
                </label>
                <label class="field">
                    <span>X (Twitter) username <small>(optional)</small></span>
                    <input type="text" name="twitter_handle" value="{{ old('twitter_handle', $profile->twitter_handle) }}" placeholder="@username">
                    @error('twitter_handle')<small class="error">{{ $message }}</small>@enderror
                </label>
                <label class="field">
                    <span>Custom share image <small>(optional · 1200×630 JPG/PNG · replaces the automatic card)</small></span>
                    <input type="file" name="seo_image" accept="image/jpeg,image/png,image/webp" data-preview="prev-share">
                    @error('seo_image')<small class="error">{{ $message }}</small>@enderror
                </label>
                @if ($profile->seo_image)
                    <label class="check"><input type="checkbox" name="remove_seo_image" value="1"> Remove custom image and use the automatic card</label>
                @endif
            </div>

            <div class="previews">
                <p class="preview-label">Google preview</p>
                <div class="serp">
                    <div class="serp-site"><span class="serp-fav">{{ mb_substr($profile->name, 0, 1) }}</span><div><b>{{ $profile->name }}</b><small>{{ $seo->url() }}</small></div></div>
                    <p class="serp-title" data-preview-text="seo_title" data-fallback="{{ $seo->title() }}">{{ $seo->title() }}</p>
                    <p class="serp-desc" data-preview-text="seo_description" data-fallback="{{ $seo->description() }}">{{ $seo->description() }}</p>
                </div>

                <p class="preview-label">Share card {{ $profile->seo_image ? '(custom)' : '(generated automatically, updates when you save)' }}</p>
                <div class="share">
                    <img id="prev-share" src="{{ $shareImage['url'] ?? '' }}" alt="" @if(!$shareImage) hidden @endif>
                    <div class="share-meta">
                        <small>{{ parse_url($seo->url(), PHP_URL_HOST) }}</small>
                        <b data-preview-text="seo_title" data-fallback="{{ $seo->title() }}">{{ $seo->title() }}</b>
                    </div>
                </div>
                @if ($shareImage)
                    <a class="hint" href="{{ $shareImage['url'] }}" target="_blank">Open full size ↗</a>
                @endif
            </div>
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

    @php $meta = $profile->hero_meta ?: \App\Models\Profile::DEFAULT_PORTRAIT_META; @endphp
    <section class="card form-card">
        <h2>Living portrait <span class="pill pill-on">{{ $profile->hasLivingPortrait() ? 'Active' : 'Not set' }}</span></h2>
        <p class="hint">
            The hero's “AI photo” effect: a transparent cut-out of you plus a grayscale <strong>depth map</strong>
            (white = near, black = far) of the same framing. The face turns toward the cursor in 3D, the eyes blink and
            follow, and the light moves. Generate both files with <code>tools/portrait/make_portrait.py</code> (see its README).
        </p>
        <div class="upload-grid">
            <div class="upload checker">
                <div class="eye-picker" data-eye-picker>
                    <img id="prev-portrait" src="{{ $profile->hero_portrait ? asset($profile->hero_portrait) : '' }}" alt="" @if(!$profile->hero_portrait) hidden @endif>
                    <span class="eye-dot" data-eye-dot="0" hidden></span>
                    <span class="eye-dot" data-eye-dot="1" hidden></span>
                </div>
                <p class="hint"><strong>Click the preview to mark the left eye, then the right eye.</strong> Do this again after uploading a new portrait.</p>
                <label class="field">
                    <span>Portrait cut-out <small>(transparent PNG/WebP, max 6 MB)</small></span>
                    <input type="file" name="hero_portrait" accept="image/png,image/webp" data-preview="prev-portrait">
                    @error('hero_portrait')<small class="error">{{ $message }}</small>@enderror
                </label>
                @error('hero_meta')<small class="error">{{ $message }}</small>@enderror
            </div>
            <div class="upload">
                <img id="prev-depth" src="{{ $profile->hero_depth ? asset($profile->hero_depth) : '' }}" alt="" @if(!$profile->hero_depth) hidden @endif>
                <label class="field">
                    <span>Depth map <small>(grayscale PNG/JPG/WebP)</small></span>
                    <input type="file" name="hero_depth" accept="image/png,image/jpeg,image/webp" data-preview="prev-depth">
                    @error('hero_depth')<small class="error">{{ $message }}</small>@enderror
                </label>
                <label class="field">
                    <span>Closed-eyes version <small>(optional · same image with eyes closed, for a realistic blink)</small></span>
                    <input type="file" name="hero_closed" accept="image/png,image/webp">
                    @error('hero_closed')<small class="error">{{ $message }}</small>@enderror
                </label>
                @if ($profile->hero_closed)
                    <p class="hint">✓ Closed-eyes image set · <a href="{{ asset($profile->hero_closed) }}" target="_blank">view</a>
                        · <label class="check" style="display:inline-flex"><input type="checkbox" name="remove_hero_closed" value="1"> remove</label></p>
                @else
                    <p class="hint">Without it, the blink is drawn procedurally.</p>
                @endif
                <label class="field">
                    <span>Pivot depth <small>(0–1 · parts brighter than this move toward the cursor)</small></span>
                    <input type="number" name="portrait_focus" min="0" max="1" step="0.01" value="{{ old('portrait_focus', $meta['focus'] ?? 0.36) }}">
                    @error('portrait_focus')<small class="error">{{ $message }}</small>@enderror
                </label>
            </div>
        </div>
        <input type="hidden" name="hero_meta" value="{{ old('hero_meta', json_encode($meta)) }}" data-meta-input>
        @if ($profile->hasLivingPortrait())
            <label class="check"><input type="checkbox" name="remove_living_portrait" value="1"> Turn off the living portrait (use the layers or photo below instead)</label>
        @endif
    </section>

    <section class="card form-card">
        <h2>Fallback: head &amp; body layers</h2>
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

@push('scripts')
<script>
    // SEO: character counters + live Google/share previews.
    document.querySelectorAll('[data-count]').forEach((input) => {
        const label = document.querySelector(`[data-count-for="${input.name}"]`);
        const targets = document.querySelectorAll(`[data-preview-text="${input.name}"]`);
        const update = () => {
            const n = input.value.length, max = +input.dataset.count;
            label.textContent = `${n} / ${max}`;
            label.classList.toggle('over', n > max);
            targets.forEach((t) => { t.textContent = input.value.trim() || t.dataset.fallback; });
        };
        input.addEventListener('input', update);
        update();
    });
</script>
<script>
    // Eye picker: click the portrait to place the left eye, then the right eye.
    (() => {
        const box = document.querySelector('[data-eye-picker]');
        const input = document.querySelector('[data-meta-input]');
        if (!box || !input) return;
        const img = box.querySelector('img');
        const dots = [...box.querySelectorAll('[data-eye-dot]')];
        let meta;
        try { meta = JSON.parse(input.value) || {}; } catch { meta = {}; }
        meta.eyes = Array.isArray(meta.eyes) ? meta.eyes : [];
        let next = 0;

        const draw = () => dots.forEach((dot, i) => {
            const e = meta.eyes[i];
            dot.hidden = !e || img.hidden;
            if (e) { dot.style.left = (e.x * 100) + '%'; dot.style.top = (e.y * 100) + '%'; }
        });

        img.addEventListener('click', (ev) => {
            const r = img.getBoundingClientRect();
            const x = (ev.clientX - r.left) / r.width;
            const y = (ev.clientY - r.top) / r.height;
            meta.eyes[next] = { x: +x.toFixed(4), y: +y.toFixed(4), rx: 0.045, ry: 0.014 };
            next = (next + 1) % 2;
            if (meta.eyes.length === 2 && meta.eyes[0] && meta.eyes[1]) {
                // Size the eye ellipses from the distance between the eyes.
                const aspect = (img.naturalWidth || 4) / (img.naturalHeight || 5);
                const dist = Math.abs(meta.eyes[1].x - meta.eyes[0].x);
                meta.eyes.forEach((e) => { e.rx = +(dist * 0.25).toFixed(4); e.ry = +(dist * 0.25 * 0.38 * aspect).toFixed(4); });
                delete meta.head; // recalculated on the server from the new eye positions
            }
            input.value = JSON.stringify(meta);
            draw();
        });
        img.addEventListener('load', draw);
        draw();
    })();
</script>
@endpush
