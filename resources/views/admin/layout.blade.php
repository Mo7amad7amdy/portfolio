@php
    $unreadCount = \App\Models\Message::unread()->count();
    $menu = [
        ['admin.dashboard', 'Dashboard', 'M3 12l9-8 9 8M5 10v10h14V10', 'admin.dashboard'],
        ['admin.analytics', 'Analytics', 'M4 20V14M10 20V8M16 20V11M22 20V4', 'admin.analytics*'],
        ['admin.profile.edit', 'Profile & Photo', 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 9a8 8 0 0 1 16 0', 'admin.profile.*'],
        ['admin.stats.index', 'Hero highlights', 'M4 20V10M10 20V4M16 20v-7M22 20H2', 'admin.stats.*'],
        ['admin.experiences.index', 'Experience', 'M3 7h18v13H3zM8 7V4h8v3', 'admin.experiences.*'],
        ['admin.projects.index', 'Projects', 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z', 'admin.projects.*'],
        ['admin.skills.index', 'Skills', 'M13 2 3 14h9l-1 8 10-12h-9z', 'admin.skills.*'],
        ['admin.certifications.index', 'Certifications', 'M12 15a6 6 0 1 0 0-12 6 6 0 0 0 0 12Zm-4 0-2 7 6-3 6 3-2-7', 'admin.certifications.*'],
        ['admin.educations.index', 'Education', 'M2 9l10-5 10 5-10 5zM6 11v5c3 2 9 2 12 0v-5', 'admin.educations.*'],
        ['admin.languages.index', 'Languages', 'M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18', 'admin.languages.*'],
        ['admin.socials.index', 'Social links', 'M10 14a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1M14 10a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1', 'admin.socials.*'],
        ['admin.messages.index', 'Messages', 'M4 4h16v12H7l-3 3z', 'admin.messages.*'],
        ['admin.account.edit', 'Account', 'M12 15v2m-6 4h12V11H6zm2-10V7a4 4 0 1 1 8 0v4', 'admin.account.*'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Dashboard') · Portfolio admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body>
<div class="shell">
    <aside class="sidebar" id="sidebar">
        <a href="{{ route('admin.dashboard') }}" class="side-brand"><span>MH</span> Portfolio</a>
        <nav>
            @foreach ($menu as [$route, $label, $icon, $pattern])
                <a href="{{ route($route) }}" class="{{ request()->routeIs($pattern) ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                    <span>{{ $label }}</span>
                    @if ($route === 'admin.messages.index' && $unreadCount)
                        <em class="count">{{ $unreadCount }}</em>
                    @endif
                </a>
            @endforeach
        </nav>
        <div class="side-foot">
            <a href="{{ route('home') }}" target="_blank" class="btn btn-light btn-block">View site ↗</a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="btn btn-text btn-block" type="submit">Log out</button>
            </form>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="menu-btn" type="button" onclick="document.body.classList.toggle('side-open')" aria-label="Toggle menu">☰</button>
            <h1>@yield('title', 'Dashboard')</h1>
            <div class="topbar-actions">@yield('actions')</div>
        </header>

        <main class="content">
            @if (session('status'))
                <div class="flash" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="flash flash-error" role="alert">Please fix the highlighted fields.</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
<div class="scrim" onclick="document.body.classList.remove('side-open')"></div>
<script>
    // Confirm destructive actions without a blocking browser dialog library.
    document.addEventListener('submit', (e) => {
        const msg = e.target.dataset.confirm;
        if (msg && !window.confirm(msg)) e.preventDefault();
    });
    // Live preview for image inputs.
    document.querySelectorAll('input[type=file][data-preview]').forEach((input) => {
        input.addEventListener('change', () => {
            const img = document.getElementById(input.dataset.preview);
            const file = input.files[0];
            if (img && file) { img.src = URL.createObjectURL(file); img.hidden = false; }
        });
    });
</script>
@stack('scripts')
</body>
</html>
