@extends('admin.layout')

@section('title', 'Messages')

@section('content')
<section class="card">
    @forelse ($messages as $message)
        <a class="msg-row {{ $message->read_at ? '' : 'unread' }}" href="{{ route('admin.messages.show', $message) }}">
            <strong>{{ $message->name }}</strong>
            <span class="msg-sub">{{ $message->subject ?: \Illuminate\Support\Str::limit($message->body, 80) }}</span>
            <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('M j, Y · H:i') }}</time>
        </a>
    @empty
        <p class="empty">No messages yet.</p>
    @endforelse
    @if ($messages->hasPages())
        <div class="pager">
            @if ($messages->previousPageUrl())<a class="btn btn-light btn-sm" href="{{ $messages->previousPageUrl() }}">← Previous</a>@endif
            <span>Page {{ $messages->currentPage() }} of {{ $messages->lastPage() }}</span>
            @if ($messages->nextPageUrl())<a class="btn btn-light btn-sm" href="{{ $messages->nextPageUrl() }}">Next →</a>@endif
        </div>
    @endif
</section>
@endsection
