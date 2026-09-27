<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Create workspace · OmniGoCRM</title><link rel="stylesheet" href="/css/app.css"></head>
<body class="auth-shell"><form class="auth-card" method="post" action="{{ route('signup.post') }}">@csrf
<div class="brand">OmniGoCRM</div><p class="muted">Create your business workspace.</p>
@if($errors->any())<div class="alert error">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
<label>Your name<input name="name" value="{{ old('name') }}" required></label>
<label>Business name<input name="company" value="{{ old('company') }}" required></label>
<label>Email<input name="email" type="email" value="{{ old('email') }}" required></label>
<label>Password<input name="password" type="password" required></label>
<label>Confirm password<input name="password_confirmation" type="password" required></label>
<button class="btn primary" type="submit">Create workspace</button><a href="{{ route('login') }}">Already have an account? Sign in</a>
</form></body></html>