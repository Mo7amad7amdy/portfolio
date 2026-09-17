@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
    <section class="welcome card">
        @if ($profile->photo)
            <img src="{{ asset($profile->photo) }}" alt="" class="avatar-lg">
        @endif
        <div>
            <p class="muted">Welcome back,</p>
            <h2>{{ $profile->name }}</h2>
            <p class="muted">{{ $profile->title }}</p>
        </div>
        <div class="welcome-actions">
            <a class="btn btn-primary" href="{{ route('admin.profile.edit') }}">Edit profile</a>
            <a class="btn btn-light" href="{{ route('home') }}" target="_blank">View site ↗</a>
        </div>
    </section>

    <section class="stat-grid">
        <a class="card stat" href="{{ route('admin.analytics') }}">
            <span>Visitors today · 7 days</span>
            <strong>{{ number_format($traffic['today']) }} · {{ number_format($traffic['week']) }}</strong>
            @if ($traffic['topCountry'])<small class="muted">Top country: {{ $traffic['topCountry'] }}</small>@endif
        </a>
        @foreach ($counts as [$label, $count, $route])
            <a class="card stat" href="{{ route($route) }}">
                <span>{{ $label }}</span>
                <strong>{{ $count }}</strong>
            </a>
        @endforeach
        <a class="card stat {{ $unread ? 'stat-alert' : '' }}" href="{{ route('admin.messages.index') }}">
            <span>Unread messages</span>
            <strong>{{ $unread }}</strong>
        </a>
    </section>

    <section class="card">
        <div class="card-head">
            <h2>Latest messages</h2>
            <a href="{{ route('admin.messages.index') }}">View all</a>
        </div>
        @forelse ($latestMessages as $message)
            <a class="msg-row {{ $message->read_at ? '' : 'unread' }}" href="{{ route('admin.messages.show', $message) }}">
                <strong>{{ $message->name }}</strong>
                <span class="msg-sub">{{ $message->subject ?: \Illuminate\Support\Str::limit($message->body, 70) }}</span>
                <time>{{ $message->created_at->diffForHumans() }}</time>
            </a>
        @empty
            <p class="empty">No messages yet. They'll show up here when someone uses the contact form.</p>
        @endforelse
    </section>
@endsection
