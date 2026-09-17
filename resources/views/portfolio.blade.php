@php
    $photo = $profile->photo ? asset($profile->photo) : null;
    $heroMode = $profile->heroMode();

    // Headline: the last two words of the title become the big display words,
    // anything before them becomes the small { brace } kicker.
    $titleWords = preg_split('/\s+/', trim($profile->title)) ?: [];
    $bigTwo = count($titleWords) > 1 ? array_pop($titleWords) : null;
    $bigOne = array_pop($titleWords) ?? '';
    $kicker = implode(' ', $titleWords);

    $topSkills = $skillGroups->flatten()->sortByDesc('level')->values();
    $notes = $topSkills->take(3)->pluck('name');
    $ghost = Str::upper($topSkills->first()->name ?? $profile->firstName());
    $companies = $experiences->pluck('company')->unique()->take(4)->values();
    $featured = $projects->firstWhere('is_featured', true) ?? $projects->first();
    $leadStat = $stats->first();
    $initials = collect(explode(' ', $profile->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    $host = fn (?string $url) => $url ? preg_replace('#^https?://(www\.)?#', '', rtrim($url, '/')) : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}">
    @if ($heroMode === 'living')
        <link rel="preload" as="image" href="{{ asset($profile->hero_portrait) }}">
    @elseif ($heroMode === 'layers')
        <link rel="preload" as="image" href="{{ asset($profile->hero_head) }}">
    @endif
</head>
<body>
<a class="skip" href="#main">Skip to content</a>

<main id="main">
    {{-- ============================ HERO ============================ --}}
    <section class="hero" id="top">
        <div class="panel hero-panel">
            <header class="nav" data-nav>
                <a href="#top" class="brand">
                    <span class="brand-mark">{{ $initials }}</span>
                    <span class="brand-name">{{ Str::lower($profile->name) }}<i>.</i></span>
                </a>
                <nav id="nav-links" class="nav-links">
                    <a href="#about">About</a>
                    @if ($experiences->isNotEmpty())<a href="#experience">Experience</a>@endif
                    @if ($projects->isNotEmpty())<a href="#work">Work</a>@endif
                    @if ($skillGroups->isNotEmpty())<a href="#stack">Stack</a>@endif
                    <a href="#contact" class="nav-mobile-only">Contact</a>
                </nav>
                <div class="nav-actions">
                    @if ($profile->cv_file)
                        <a href="{{ asset($profile->cv_file) }}" class="icon-btn" download aria-label="Download CV" title="Download CV">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v11m0 0 4.5-4.5M12 15l-4.5-4.5M5 19h14"/></svg>
                        </a>
                    @endif
                    <a href="#contact" class="btn btn-dark">Contact</a>
                    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-links" data-nav-toggle>
                        <span></span><span></span><span class="sr-only">Menu</span>
                    </button>
                </div>
            </header>

            <div class="hero-ghost" aria-hidden="true">{{ $ghost }}</div>

            <div class="hero-intro">
                <p class="byline">{{ $profile->name }} — {{ $profile->title }}</p>
                @if ($profile->tagline)
                    <p class="statement">{{ $profile->tagline }}</p>
                @endif
            </div>

            <h1 class="hero-title">
                <span class="sr-only">{{ $profile->name }}, {{ $profile->title }}</span>
                <span class="word word-1" aria-hidden="true">
                    @if ($kicker)<span class="brace-kicker">{ {{ $kicker }} }</span>@endif
                    {{ $bigOne }}
                </span>
                @if ($bigTwo)
                    <span class="word word-2" aria-hidden="true">{{ $bigTwo }}</span>
                @endif
            </h1>

            <div class="hero-visual" data-face-stage data-mode="{{ $heroMode }}"
                 @if ($heroMode === 'living')
                     data-portrait="{{ asset($profile->hero_portrait) }}"
                     data-depth-map="{{ asset($profile->hero_depth) }}"
                     @if ($profile->hero_closed) data-closed="{{ asset($profile->hero_closed) }}" @endif
                     data-meta="{{ json_encode($profile->hero_meta) }}"
                 @endif>
                <div class="portrait">
                    @if ($heroMode === 'living')
                        <canvas class="layer layer-canvas" data-living-canvas aria-hidden="true"></canvas>
                        <img class="layer layer-portrait" data-head src="{{ asset($profile->hero_portrait) }}" alt="{{ $profile->name }}" width="1000" height="1249">
                    @elseif ($heroMode === 'layers')
                        <img class="layer layer-body" data-body src="{{ asset($profile->hero_body) }}" alt="" width="900" height="1124">
                        <img class="layer layer-head" data-head src="{{ asset($profile->hero_head) }}" alt="{{ $profile->name }}" width="900" height="1124">
                    @elseif ($photo)
                        <img class="layer layer-photo" data-head src="{{ $photo }}" alt="{{ $profile->name }}">
                    @endif
                </div>
                @foreach ($notes as $i => $note)
                    <span class="note note-{{ $i + 1 }}" data-depth="{{ [0.6, -0.5, 0.8][$i] }}"><i>{</i> {{ $note }} <i>}</i></span>
                @endforeach
            </div>

            <div class="hero-proof">
                @if ($companies->isNotEmpty())
                    <ul class="stack-avatars" aria-label="Companies I've worked with">
                        @foreach ($companies as $company)
                            <li title="{{ $company }}">{{ Str::upper(mb_substr($company, 0, 2)) }}</li>
                        @endforeach
                    </ul>
                @endif
                @if ($leadStat)
                    <p class="proof-value">{{ $leadStat->value }}</p>
                    <p class="proof-label">{{ $leadStat->label }}{{ $companies->isNotEmpty() ? ' — at '.$companies->implode(', ') : '' }}.</p>
                @endif
                <p class="proof-check">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7"/></svg>
                    {{ $profile->open_to_work ? 'Available for new projects' : 'Currently fully booked' }}@if ($profile->location) · {{ $profile->location }}@endif
                </p>
            </div>

            @if ($socials->isNotEmpty())
                <ul class="social-icons hero-socials" aria-label="Social media">
                    @foreach ($socials->take(6) as $social)
                        <li><a href="{{ $social->url }}" target="_blank" rel="noopener me" aria-label="{{ $social->name }}" title="{{ $social->name }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $social->icon }}"/></svg></a></li>
                    @endforeach
                </ul>
            @endif

            <a href="#work" class="btn btn-accent hero-cta">See my work</a>

            @if ($featured)
                <a href="#work" class="hero-feature">
                    <span class="feature-thumb" aria-hidden="true">
                        <span class="term-dots"><i></i><i></i><i></i></span>
                        <code><b>$</b> php artisan serve</code>
                        <code class="dim">GET /api/v1/auctions … <em>200</em> 38ms</code>
                        <code class="dim">WS  bid.placed … <em>ok</em></code>
                    </span>
                    <span class="feature-text">
                        <small>{ Featured }</small>
                        <strong>{{ $featured->title }}</strong>
                        <span>{{ Str::limit($featured->summary, 70) }}</span>
                    </span>
                </a>
            @endif
        </div>
    </section>

    {{-- ============================ STACK MARQUEE ============================ --}}
    @if ($topSkills->isNotEmpty())
        <div class="marquee" aria-hidden="true">
            <div class="marquee-track">
                @for ($r = 0; $r < 2; $r++)
                    @foreach ($topSkills->take(12) as $skill)
                        <span>{{ $skill->name }}</span><i>✦</i>
                    @endforeach
                @endfor
            </div>
        </div>
    @endif

    {{-- ============================ ABOUT ============================ --}}
    <section class="section" id="about">
        <div class="wrap">
            <div class="section-label reveal"><span>/01</span> About</div>
            <div class="about-grid">
                <div class="reveal">
                    @php $paras = $profile->paragraphs(); @endphp
                    @if ($paras)
                        <p class="lede">{{ $paras[0] }}</p>
                        @foreach (array_slice($paras, 1) as $p)
                            <p class="body-copy">{{ $p }}</p>
                        @endforeach
                    @endif
                </div>
                <aside class="about-photo reveal">
                    @if ($photo)
                        <img src="{{ $photo }}" alt="{{ $profile->name }}" loading="lazy">
                    @endif
                    <dl class="facts">
                        @if ($profile->location)<div><dt>Based in</dt><dd>{{ $profile->location }}</dd></div>@endif
                        @if ($languages->isNotEmpty())<div><dt>Speaks</dt><dd>{{ $languages->pluck('name')->implode(', ') }}</dd></div>@endif
                        @if ($educations->isNotEmpty())<div><dt>Studied</dt><dd>{{ $educations->first()->degree }}</dd></div>@endif
                    </dl>
                </aside>
            </div>

            @if ($stats->isNotEmpty())
                <ul class="numbers reveal">
                    @foreach ($stats as $stat)
                        <li><strong>{{ $stat->value }}</strong><span>{{ $stat->label }}</span></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    {{-- ============================ EXPERIENCE ============================ --}}
    @if ($experiences->isNotEmpty())
        <section class="section section-panel" id="experience">
            <div class="panel">
                <div class="wrap">
                    <div class="section-head reveal">
                        <div class="section-label"><span>/02</span> Experience</div>
                        <h2 class="section-title">Where I've shipped <span class="muted">backends that stay up under real traffic.</span></h2>
                    </div>
                    <ol class="jobs">
                        @foreach ($experiences as $job)
                            <li class="job reveal">
                                <div class="job-when">
                                    <span>{{ $job->period }}</span>
                                    @if ($job->is_current)<em class="live-dot">Now</em>@endif
                                </div>
                                <div class="job-who">
                                    <h3>{{ $job->company }}</h3>
                                    <p>{{ $job->role }}</p>
                                    <small>{{ collect([$job->company_type, $job->location])->filter()->implode(' · ') }}</small>
                                </div>
                                <div class="job-what">
                                    @if ($bullets = $job->bullets())
                                        <ul>@foreach ($bullets as $b)<li>{{ $b }}</li>@endforeach</ul>
                                    @endif
                                    @if ($tech = $job->techList())
                                        <p class="tech">{{ implode(' / ', $tech) }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </section>
    @endif

    {{-- ============================ WORK ============================ --}}
    @if ($projects->isNotEmpty())
        <section class="section" id="work">
            <div class="wrap">
                <div class="section-head reveal">
                    <div class="section-label"><span>/03</span> Selected work</div>
                    <h2 class="section-title">APIs and platforms <span class="muted">I built the backend for.</span></h2>
                </div>
                <div class="work-grid">
                    @foreach ($projects->where('is_featured', true) as $project)
                        <article class="work-card reveal">
                            <div class="work-top">
                                <span class="work-index">/{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                @if ($project->client)<span class="brace-note"><i>{</i> {{ $project->client }} <i>}</i></span>@endif
                            </div>
                            <h3>{{ $project->title }}</h3>
                            <p>{{ $project->summary }}</p>
                            @if ($tech = $project->techList())
                                <p class="tech">{{ implode(' / ', $tech) }}</p>
                            @endif
                            @if ($project->url)
                                <a class="work-link" href="{{ $project->url }}" target="_blank" rel="noopener">Visit project <span aria-hidden="true">→</span></a>
                            @endif
                        </article>
                    @endforeach
                </div>

                @php $more = $projects->where('is_featured', false); $offset = $projects->where('is_featured', true)->count(); @endphp
                @if ($more->isNotEmpty())
                    <ul class="work-list">
                        @foreach ($more as $project)
                            <li class="reveal">
                                <span class="work-index">/{{ str_pad($offset + $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <strong>
                                    @if ($project->url)
                                        <a href="{{ $project->url }}" target="_blank" rel="noopener">{{ $project->title }}</a>
                                    @else
                                        {{ $project->title }}
                                    @endif
                                </strong>
                                <span class="work-sum">{{ $project->summary }}</span>
                                <span class="work-client">{{ $project->client }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    @endif

    {{-- ============================ STACK ============================ --}}
    @if ($skillGroups->isNotEmpty())
        <section class="section section-panel" id="stack">
            <div class="panel">
                <div class="wrap">
                    <div class="section-head reveal">
                        <div class="section-label"><span>/04</span> Stack</div>
                        <h2 class="section-title">Tools I use <span class="muted">every day — and the ones I reach for when it matters.</span></h2>
                    </div>
                    <div class="stack-grid">
                        @foreach ($skillGroups as $category => $skills)
                            <div class="stack-col reveal">
                                <h3>{{ $category }}</h3>
                                <ul>
                                    @foreach ($skills as $skill)
                                        <li class="{{ ($skill->level ?? 0) >= 90 ? 'core' : '' }}">{{ $skill->name }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>

                    @if ($certifications->isNotEmpty() || $educations->isNotEmpty())
                        <div class="creds reveal">
                            @foreach ($educations as $edu)
                                <div class="cred">
                                    <span>{{ collect([$edu->start_year, $edu->end_year])->filter()->implode('–') }}</span>
                                    <strong>{{ $edu->degree }}</strong>
                                    <small>{{ $edu->institution }}@if($edu->location), {{ $edu->location }}@endif</small>
                                </div>
                            @endforeach
                            @foreach ($certifications as $cert)
                                <div class="cred">
                                    <span>{{ $cert->year }}</span>
                                    <strong>{{ $cert->title }}</strong>
                                    <small>{{ $cert->issuer }}</small>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ============================ CONTACT ============================ --}}
    <section class="section" id="contact">
        <div class="wrap contact">
            <div class="reveal">
                <div class="section-label"><span>/05</span> Contact</div>
                <h2 class="contact-title">Let's build something <span class="accent">{ reliable }</span> together.</h2>
                @if ($profile->email)
                    <a class="contact-mail" href="mailto:{{ $profile->email }}">{{ Str::lower($profile->email) }} <span aria-hidden="true">↗</span></a>
                @endif
                <ul class="contact-links">
                    @if ($profile->phone && ! $socials->contains('platform', 'whatsapp'))
                        <li><span>WhatsApp</span><a href="https://wa.me/{{ preg_replace('/\D+/', '', $profile->phone) }}" target="_blank" rel="noopener">{{ $profile->phone }}</a></li>
                    @endif
                    @foreach ($socials as $social)
                        <li>
                            <span class="social-name"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $social->icon }}"/></svg>{{ $social->name }}</span>
                            <a href="{{ $social->url }}" target="_blank" rel="noopener me">{{ $social->display }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <form class="contact-form reveal" method="POST" action="{{ route('contact.store') }}" novalidate>
                @csrf
                @if (session('contact_status'))
                    <p class="alert" role="status">{{ session('contact_status') }}</p>
                @endif
                <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                <label class="field">
                    <span>Your name</span>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name">
                    @error('name')<small class="error">{{ $message }}</small>@enderror
                </label>
                <label class="field">
                    <span>Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email">
                    @error('email')<small class="error">{{ $message }}</small>@enderror
                </label>
                <label class="field">
                    <span>Subject <i>(optional)</i></span>
                    <input type="text" name="subject" value="{{ old('subject') }}" maxlength="190">
                    @error('subject')<small class="error">{{ $message }}</small>@enderror
                </label>
                <label class="field">
                    <span>Tell me about the project</span>
                    <textarea name="body" rows="4" required minlength="10" maxlength="5000">{{ old('body') }}</textarea>
                    @error('body')<small class="error">{{ $message }}</small>@enderror
                </label>
                <button class="btn btn-accent" type="submit">Send message</button>
            </form>
        </div>
    </section>
</main>

<footer class="footer">
    <div class="panel footer-panel">
        <div class="footer-top">
            <p>© {{ date('Y') }} {{ $profile->name }}</p>
            @if ($socials->isNotEmpty())
                <ul class="social-icons" aria-label="Social media">
                    @foreach ($socials as $social)
                        <li><a href="{{ $social->url }}" target="_blank" rel="noopener me" aria-label="{{ $social->name }}" title="{{ $social->name }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $social->icon }}"/></svg></a></li>
                    @endforeach
                </ul>
            @endif
            <nav>
                <a href="#about">About</a>
                <a href="#work">Work</a>
                <a href="#contact">Contact</a>
                <a href="#top">Back to top ↑</a>
            </nav>
        </div>
        <div class="footer-word" aria-hidden="true" style="--footer-size: {{ round(150 / max(8, mb_strlen($profile->name) + 1), 2) }}vw">{{ Str::lower($profile->name) }}<i>.</i></div>
    </div>
</footer>

@if ($visitUuid = request()->attributes->get('analytics.uuid'))
<script src="{{ asset('js/track.js') }}?v={{ filemtime(public_path('js/track.js')) }}" data-visit="{{ $visitUuid }}" data-endpoint="{{ route('analytics.collect') }}" defer></script>
@endif
@if ($heroMode === 'living')
<script src="{{ asset('js/living-portrait.js') }}?v={{ filemtime(public_path('js/living-portrait.js')) }}" defer></script>
@endif
<script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path('js/site.js')) }}" defer></script>
</body>
</html>
