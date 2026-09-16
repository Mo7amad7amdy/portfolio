@extends('admin.layout')

@section('title', 'Message')

@section('actions')
    <a class="btn btn-light" href="{{ route('admin.messages.index') }}">← All messages</a>
@endsection

@section('content')
<article class="card form-card message">
    <header class="message-head">
        <div>
            <h2>{{ $message->subject ?: 'No subject' }}</h2>
            <p class="muted">From <strong>{{ $message->name }}</strong> &lt;<a href="mailto:{{ $message->email }}">{{ $message->email }}</a>&gt;</p>
        </div>
        <time class="muted">{{ $message->created_at->format('M j, Y · H:i') }}</time>
    </header>
    <div class="message-body">{!! nl2br(e($message->body)) !!}</div>
    <div class="form-actions">
        <a class="btn btn-primary" href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.($message->subject ?: 'Your message')) }}">Reply by email</a>
        <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" data-confirm="Delete this message?">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger" type="submit">Delete</button>
        </form>
    </div>
</article>
@endsection
