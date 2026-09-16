@php
    $photo = $profile->photo ? asset($profile->photo) : null;
    $orbit = $skillGroups->flatten()->sortByDesc('level')->take(6)->pluck('name');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $profile->name }} — {{ $profile->title }}</title>
    <meta name="description" content="{{ $profile->tagline ?: $profile->title }}">
    <meta property="og:title" content="{{ $profile->name }} — {{ $profile->title }}">
    <meta property="og:description" content="{{ $profile->tagline }}">
    @if ($photo)<meta property="og:image" content="{{ $photo }}">@endif
    <meta name="theme-color" content="#0f0c09">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><rect width='64' height='64' rx='16' fill='%23f5a524'/><text x='50%25' y='56%25' font-family='Arial' font-weight='700' font-size='26' text-anchor='middle' dominant-baseline='middle' fill='%230f0c09'>MH</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}">
    @if ($profile->hasHeroLayers())
        <link rel="preload" as="image" href="{{ asset($profile->hero_head) }}">
        <link rel="preload" as="image" href="{{ asset($profile->hero_body) }}">
    @endif
</head>
<body>
<a class="skip" href="#main">Skip to content</a>

<header class="nav" data-nav>
    <div class="container nav-inner">
        <a href="#top" class="brand" aria-label="Home">
            <span class="brand-mark">{{ collect(explode(' ', $profile->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}</span>
            <span class="brand-name">{{ $profile->name }}</span>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-links" data-nav-toggle>
            <span></span><span></span><span class="sr-only">Menu</span>
        </button>
        <nav id="nav-links" class="nav-links">
            <a href="#about">About</a>
            @if ($experiences->isNotEmpty())<a href="#experience">Experience</a>@endif
            @if ($skillGroups->isNotEmpty())<a href="#skills">Skills</a>@endif
            @if ($projects->isNotEmpty())<a href="#projects">Projects</a>@endif
            <a href="#contact" class="btn btn-sm btn-primary">Hire me</a>
        </nav>
    </div>
</header>

<main id="main">
    {{-- ================= HERO ================= --}}
    <section class="hero" id="top">
        <div class="hero-bg" aria-hidden="true"><div class="grid"></div><div class="spot" data-spot></div></div>
        <div class="container hero-inner">
            <div class="hero-copy">
                @if ($profile->open_to_work)
                    <span class="eyebrow"><i class="pulse"></i> Available for new projects @if($profile->location)· {{ $profile->location }}@endif</span>
                @endif
                <h1 class="hero-title">
                    <span class="hello">Hi, I'm {{ $profile->firstName() }}</span>
                    <span class="role">{{ $profile->title }}</span>
                </h1>
                @if ($profile->tagline)
                    <p class="lead">{{ $profile->tagline }}</p>
                @endif
                <div class="cta">
                    <a href="#projects" class="btn btn-primary">See my work
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                    @if ($profile->cv_file)
                        <a href="{{ asset($profile->cv_file) }}" class="btn btn-ghost" download>Download CV</a>
                    @endif
                    <div class="socials">
                        @if ($profile->github_url)
                            <a href="{{ $profile->github_url }}" target="_blank" rel="noopener" aria-label="GitHub">
                                <svg viewBox="0 0 24 24"><path fill="currentColor" stroke="none" d="M12 .5a12 12 0 0 0-3.8 23.4c.6.1.8-.3.8-.6v-2c-3.3.7-4-1.6-4-1.6-.6-1.4-1.4-1.8-1.4-1.8-1-.7.1-.7.1-.7 1.2.1 1.8 1.2 1.8 1.2 1 1.8 2.8 1.3 3.5 1 .1-.8.4-1.3.7-1.6-2.7-.3-5.5-1.3-5.5-6 0-1.3.5-2.4 1.2-3.2-.1-.3-.5-1.5.1-3.2 0 0 1-.3 3.3 1.2a11.5 11.5 0 0 1 6 0C17.3 4.6 18.3 5 18.3 5c.7 1.7.2 2.9.1 3.2.8.8 1.2 1.9 1.2 3.2 0 4.6-2.8 5.6-5.5 5.9.4.4.8 1.1.8 2.2v3.3c0 .3.2.7.8.6A12 12 0 0 0 12 .5Z"/></svg>
                            </a>
                        @endif
                        @if ($profile->linkedin_url)
                            <a href="{{ $profile->linkedin_url }}" target="_blank" rel="noopener" aria-label="LinkedIn">
                                <svg viewBox="0 0 24 24"><path fill="currentColor" stroke="none" d="M20.4 20.5h-3.6v-5.6c0-1.3 0-3-1.8-3s-2.1 1.4-2.1 2.9v5.7H9.3V9h3.4v1.6c.5-.9 1.6-1.8 3.4-1.8 3.6 0 4.3 2.4 4.3 5.5v6.2ZM5.3 7.4a2.1 2.1 0 1 1 0-4.2 2.1 2.1 0 0 1 0 4.2ZM7.1 20.5H3.5V9h3.6v11.5ZM22.2 0H1.8C.8 0 0 .8 0 1.7v20.6c0 .9.8 1.7 1.8 1.7h20.4c1 0 1.8-.8 1.8-1.7V1.7C24 .8 23.2 0 22.2 0Z"/></svg>
                            </a>
                        @endif
                    </div>
                </div>
                @if ($stats->isNotEmpty())
                    <ul class="stats">
                        @foreach ($stats as $stat)
                            <li><strong>{{ $stat->value }}</strong><span>{{ $stat->label }}</span></li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="hero-visual" data-face-stage data-mode="{{ $profile->hasHeroLayers() ? 'layers' : 'photo' }}">
                <div class="halo" data-depth="-0.6" aria-hidden="true"></div>
                <div class="orbit" aria-hidden="true">
                    @foreach ($orbit as $i => $name)
                        <span class="chip" style="--i: {{ $i }}; --n: {{ $orbit->count() }}"><span>{{ $name }}</span></span>
                    @endforeach
                </div>
                <div class="portrait">
                    @if ($profile->hasHeroLayers())
                        <img class="layer layer-body" data-body src="{{ asset($profile->hero_body) }}" alt="" width="900" height="1124">
                        <img class="layer layer-head" data-head src="{{ asset($profile->hero_head) }}" alt="{{ $profile->name }}" width="900" height="1124">
                    @elseif ($photo)
                        <img class="layer layer-photo" data-head src="{{ $photo }}" alt="{{ $profile->name }}">
                    @endif
                </div>
                <div class="float-card fc-1" data-depth="1.2" aria-hidden="true">
                    <code><span class="k">return</span> response()<span class="p">-&gt;</span>json(<span class="s">'ok'</span>);</code>
                </div>
                <div class="float-card fc-2" data-depth="0.8" aria-hidden="true">
                    <span class="status"></span> <span>All systems <b>operational</b></span>
                </div>
            </div>
        </div>
        <a href="#about" class="scroll-hint" aria-label="Scroll to about"><span></span></a>
    </section>

    {{-- ================= ABOUT ================= --}}
    <section class="section" id="about">
        <div class="container about">
            <div class="reveal">
                <p class="kicker">About me</p>
                <h2 class="section-title">Backend engineer who cares about the <em>boring</em> stuff — speed, security, reliability.</h2>
                @foreach ($profile->paragraphs() as $paragraph)
                    <p class="muted">{{ $paragraph }}</p>
                @endforeach
            </div>
            <aside class="card facts reveal">
                @if ($photo)
                    <img src="{{ $photo }}" alt="{{ $profile->name }}" class="facts-photo" loading="lazy">
                @endif
                <dl>
                    @if ($profile->location)<div><dt>Based in</dt><dd>{{ $profile->location }}</dd></div>@endif
                    @if ($profile->email)<div><dt>Email</dt><dd><a href="mailto:{{ $profile->email }}">{{ $profile->email }}</a></dd></div>@endif
                    @if ($profile->phone)<div><dt>Phone</dt><dd><a href="tel:{{ preg_replace('/\s+/', '', $profile->phone) }}">{{ $profile->phone }}</a></dd></div>@endif
                    @if ($languages->isNotEmpty())
                        <div><dt>Languages</dt><dd>{{ $languages->map(fn ($l) => "{$l->name} ({$l->level})")->implode(' · ') }}</dd></div>
                    @endif
                </dl>
            </aside>
        </div>
    </section>

    {{-- ================= EXPERIENCE ================= --}}
    @if ($experiences->isNotEmpty())
    <section class="section section-alt" id="experience">
        <div class="container">
            <div class="section-head reveal">
                <p class="kicker">Experience</p>
                <h2 class="section-title">Where I've shipped</h2>
            </div>
            <ol class="timeline">
                @foreach ($experiences as $job)
                    <li class="timeline-item reveal {{ $job->is_current ? 'is-current' : '' }}">
                        <div class="timeline-meta">
                            <span class="period">{{ $job->period }}</span>
                            @if ($job->location)<span class="loc">{{ $job->location }}</span>@endif
                        </div>
                        <article class="card job">
                            <header>
                                <h3>{{ $job->role }}</h3>
                                <p class="company">{{ $job->company }}@if ($job->company_type)<span> · {{ $job->company_type }}</span>@endif
                                    @if ($job->is_current)<em class="badge">Current</em>@endif
                                </p>
                            </header>
                            @if ($bullets = $job->bullets())
                                <ul class="bullets">
                                    @foreach ($bullets as $bullet)<li>{{ $bullet }}</li>@endforeach
                                </ul>
                            @endif
                            @if ($tech = $job->techList())
                                <ul class="tags">@foreach ($tech as $t)<li>{{ $t }}</li>@endforeach</ul>
                            @endif
                        </article>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>
    @endif

    {{-- ================= SKILLS ================= --}}
    @if ($skillGroups->isNotEmpty())
    <section class="section" id="skills">
        <div class="container">
            <div class="section-head reveal">
                <p class="kicker">Skills</p>
                <h2 class="section-title">The toolbox</h2>
                <p class="muted">Highlighted items are what I reach for every day.</p>
            </div>
            <div class="skills-grid">
                @foreach ($skillGroups as $category => $skills)
                    <div class="card skill-card reveal">
                        <h3>{{ $category }}</h3>
                        <ul class="tags">
                            @foreach ($skills as $skill)
                                <li class="{{ ($skill->level ?? 0) >= 90 ? 'core' : '' }}">{{ $skill->name }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ================= PROJECTS ================= --}}
    @if ($projects->isNotEmpty())
    <section class="section section-alt" id="projects">
        <div class="container">
            <div class="section-head reveal">
                <p class="kicker">Selected work</p>
                <h2 class="section-title">Projects I built the backend for</h2>
            </div>
            <div class="projects">
                @foreach ($projects as $project)
                    <article class="card project reveal {{ $project->is_featured ? 'featured' : '' }}" data-tilt>
                        <div class="project-top">
                            <span class="project-index">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            @if ($project->client)<span class="project-client">{{ $project->client }}</span>@endif
                        </div>
                        <h3>
                            @if ($project->url)
                                <a href="{{ $project->url }}" target="_blank" rel="noopener">{{ $project->title }} <span aria-hidden="true">↗</span></a>
                            @else
                                {{ $project->title }}
                            @endif
                        </h3>
                        <p class="muted">{{ $project->summary }}</p>
                        @if ($tech = $project->techList())
                            <ul class="tags">@foreach ($tech as $t)<li>{{ $t }}</li>@endforeach</ul>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ================= CREDENTIALS ================= --}}
    @if ($certifications->isNotEmpty() || $educations->isNotEmpty())
    <section class="section" id="credentials">
        <div class="container creds">
            @if ($educations->isNotEmpty())
                <div class="reveal">
                    <p class="kicker">Education</p>
                    @foreach ($educations as $edu)
                        <div class="card cred">
                            <span class="cred-year">{{ collect([$edu->start_year, $edu->end_year])->filter()->implode(' — ') }}</span>
                            <h3>{{ $edu->degree }}</h3>
                            <p class="muted">{{ $edu->institution }}@if($edu->location), {{ $edu->location }}@endif</p>
                        </div>
                    @endforeach
                </div>
            @endif
            @if ($certifications->isNotEmpty())
                <div class="reveal">
                    <p class="kicker">Certifications</p>
                    @foreach ($certifications as $cert)
                        <div class="card cred">
                            @if ($cert->year)<span class="cred-year">{{ $cert->year }}</span>@endif
                            <h3>{{ $cert->title }}</h3>
                            @if ($cert->issuer)<p class="muted">{{ $cert->issuer }}</p>@endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
    @endif

    {{-- ================= CONTACT ================= --}}
    <section class="section section-alt" id="contact">
        <div class="container contact">
            <div class="reveal">
                <p class="kicker">Contact</p>
                <h2 class="section-title">Have a backend that needs to be faster, safer, or just <em>done</em>?</h2>
                <p class="muted">Tell me about the project. I usually reply within a day.</p>
                <ul class="contact-list">
                    @if ($profile->email)<li><span>Email</span><a href="mailto:{{ $profile->email }}">{{ $profile->email }}</a></li>@endif
                    @if ($profile->phone)<li><span>Phone / WhatsApp</span><a href="https://wa.me/{{ preg_replace('/\D+/', '', $profile->phone) }}" target="_blank" rel="noopener">{{ $profile->phone }}</a></li>@endif
                    @if ($profile->linkedin_url)<li><span>LinkedIn</span><a href="{{ $profile->linkedin_url }}" target="_blank" rel="noopener">{{ preg_replace('#^https?://(www\.)?#', '', $profile->linkedin_url) }}</a></li>@endif
                    @if ($profile->github_url)<li><span>GitHub</span><a href="{{ $profile->github_url }}" target="_blank" rel="noopener">{{ preg_replace('#^https?://(www\.)?#', '', $profile->github_url) }}</a></li>@endif
                </ul>
            </div>

            <form class="card contact-form reveal" method="POST" action="{{ route('contact.store') }}" novalidate>
                @csrf
                @if (session('contact_status'))
                    <p class="alert alert-success" role="status">{{ session('contact_status') }}</p>
                @endif
                <div class="hp" aria-hidden="true">
                    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>
                <div class="row-2">
                    <label class="field">
                        <span>Name</span>
                        <input type="text" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name">
                        @error('name')<small class="error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email">
                        @error('email')<small class="error">{{ $message }}</small>@enderror
                    </label>
                </div>
                <label class="field">
                    <span>Subject <i>(optional)</i></span>
                    <input type="text" name="subject" value="{{ old('subject') }}" maxlength="190">
                    @error('subject')<small class="error">{{ $message }}</small>@enderror
                </label>
                <label class="field">
                    <span>Message</span>
                    <textarea name="body" rows="5" required minlength="10" maxlength="5000">{{ old('body') }}</textarea>
                    @error('body')<small class="error">{{ $message }}</small>@enderror
                </label>
                <button class="btn btn-primary btn-block" type="submit">Send message</button>
            </form>
        </div>
    </section>
</main>

<footer class="footer">
    <div class="container footer-inner">
        <p>© {{ date('Y') }} {{ $profile->name }}. Built with Laravel.</p>
        <a href="#top">Back to top ↑</a>
    </div>
</footer>

<script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path('js/site.js')) }}" defer></script>
</body>
</html>
