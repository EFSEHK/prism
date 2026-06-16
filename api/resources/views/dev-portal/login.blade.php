@extends('layout')

@section('title', 'Developer access')

@section('styles')
    <style>
        body { background: var(--bg); }
        .page {
            max-width: 440px;
            margin: 0 auto;
            padding: 48px 20px;
        }
        .panel {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 24px;
            padding: 28px;
            box-shadow: var(--shadow);
        }
        h1 { margin: 0 0 8px; font-size: 1.5rem; }
        p { margin: 0 0 22px; color: var(--muted); line-height: 1.6; }
        .field { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; }
        .field label { font-weight: 600; font-size: 0.92rem; }
        .field input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid var(--input-border);
            background: var(--input-bg);
            font: inherit;
            color: var(--text);
        }
        .error-list, .flash-card {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 14px;
            font-size: 0.92rem;
        }
        .error-list { background: var(--error-bg); border: 1px solid var(--error-border); color: var(--error-text); }
        .flash-card { background: var(--success-bg); border: 1px solid var(--success-border); color: var(--success-text); }
        .back { display: inline-block; margin-top: 18px; color: var(--muted); font-size: 0.92rem; }
    </style>
@endsection

@section('content')
    <div class="page">
        <div class="panel">
            <h1>Developer portal</h1>
            <p>Restricted access for <strong>developer</strong> and <strong>superadmin</strong> accounts only.</p>

            @if ($errors->any())
                <div class="error-list">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @if (session('status'))
                <div class="flash-card">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('dev-portal.login.store') }}">
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required>
                </div>
                <label style="display:flex;align-items:center;gap:8px;margin-bottom:18px;color:var(--muted);font-size:0.92rem;">
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    Remember me
                </label>
                <button type="submit" class="button button-primary" style="width:100%;">Sign in</button>
            </form>

            <a href="{{ route('welcome') }}" class="back">← Back to welcome page</a>
        </div>
    </div>
@endsection
