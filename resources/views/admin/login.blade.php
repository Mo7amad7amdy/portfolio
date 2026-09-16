<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Sign in · Portfolio admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body class="auth">
<form class="auth-card" method="POST" action="{{ route('login.store') }}">
    @csrf
    <div class="side-brand"><span>MH</span> Portfolio</div>
    <h1>Sign in to your dashboard</h1>
    <label class="field">
        <span>Email</span>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        @error('email')<small class="error">{{ $message }}</small>@enderror
    </label>
    <label class="field">
        <span>Password</span>
        <input type="password" name="password" required autocomplete="current-password">
        @error('password')<small class="error">{{ $message }}</small>@enderror
    </label>
    <label class="check"><input type="checkbox" name="remember" value="1"> Remember me</label>
    <button class="btn btn-primary btn-block" type="submit">Sign in</button>
    <a class="back" href="{{ route('home') }}">← Back to site</a>
</form>
</body>
</html>
