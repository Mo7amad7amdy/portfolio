@extends('admin.layout')

@section('title', 'Account')

@section('content')
<form method="POST" action="{{ route('admin.account.update') }}" class="card form-card narrow">
    @csrf
    @method('PUT')
    <h2>Login details</h2>
    <label class="field">
        <span>Name</span>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
        @error('name')<small class="error">{{ $message }}</small>@enderror
    </label>
    <label class="field">
        <span>Login email</span>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username">
        @error('email')<small class="error">{{ $message }}</small>@enderror
    </label>
    <label class="field">
        <span>New password <small>(leave blank to keep the current one)</small></span>
        <input type="password" name="password" autocomplete="new-password">
        @error('password')<small class="error">{{ $message }}</small>@enderror
    </label>
    <label class="field">
        <span>Confirm new password</span>
        <input type="password" name="password_confirmation" autocomplete="new-password">
    </label>
    <hr>
    <label class="field">
        <span>Current password <small>(required to save)</small></span>
        <input type="password" name="current_password" required autocomplete="current-password">
        @error('current_password')<small class="error">{{ $message }}</small>@enderror
    </label>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Update account</button>
    </div>
</form>
@endsection
