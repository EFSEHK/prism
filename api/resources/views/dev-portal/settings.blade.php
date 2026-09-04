@extends('layout')

@section('title', 'Release settings')

@section('styles')
    <style>
        body { background: var(--bg); }
        .page { max-width: 820px; margin: 0 auto; padding: 32px 20px 48px; }
        .panel {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 24px;
            padding: 28px;
            box-shadow: var(--shadow);
        }
        .header { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
        h1 { margin: 0; font-size: 1.6rem; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .field { display: flex; flex-direction: column; gap: 8px; margin-bottom: 18px; }
        .field label { font-weight: 600; font-size: 0.92rem; }
        .field input, .field textarea {
            width: 100%;
            padding: 12px 14px;
            border-radius: 14px;
            border: 1px solid var(--input-border);
            background: var(--input-bg);
            font: inherit;
            color: var(--text);
        }
        .field textarea { min-height: 100px; resize: vertical; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .section-title { margin: 28px 0 14px; font-size: 1.05rem; }
        .hint { color: var(--muted); font-size: 0.88rem; line-height: 1.5; margin-top: 6px; }
        .flash-card, .error-list {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 14px;
            font-size: 0.92rem;
        }
        .flash-card { background: var(--success-bg); border: 1px solid var(--success-border); color: var(--success-text); }
        .error-list { background: var(--error-bg); border: 1px solid var(--error-border); color: var(--error-text); }
        .current-file { margin-top: 8px; font-size: 0.9rem; color: var(--muted); }
        .current-file a { color: var(--primary); text-decoration: underline; }
        .current-release {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 18px;
            align-items: center;
            margin: 0 0 18px;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid var(--info-border);
            background: var(--info-bg);
            color: var(--info-text);
            font-size: 0.95rem;
        }
        .current-release strong { font-weight: 700; }
        .current-release code {
            padding: 2px 8px;
            border-radius: 8px;
            background: var(--soft-badge);
            color: var(--text);
        }
        @media (max-width: 720px) { .grid { grid-template-columns: 1fr; } }
    </style>
@endsection

@section('content')
    <div class="page">
        <div class="panel">
            <div class="header">
                <div style="display:flex;align-items:center;gap:12px;">
                    <img class="app-logo app-logo-sm" src="{{ asset('logo.png') }}" alt="{{ config('app.name') }} logo" width="40" height="40">
                    <h1>Release settings</h1>
                </div>
                <div class="actions">
                    <a href="{{ route('dashboard') }}" class="button button-secondary">Dashboard</a>
                    <a href="{{ route('welcome') }}" class="button button-secondary">Welcome page</a>
                    <form method="POST" action="{{ route('dev-portal.logout') }}">
                        @csrf
                        <button type="submit" class="button button-secondary">Logout</button>
                    </form>
                </div>
            </div>

            @if (session('status'))
                <div class="flash-card">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="error-list">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('dev-portal.settings.update') }}" enctype="multipart/form-data">
                @csrf

                <h2 class="section-title">Web application</h2>
                <div class="field">
                    <label for="web_app_url">Web app URL</label>
                    <input id="web_app_url" type="url" name="web_app_url" value="{{ old('web_app_url', $settings->web_app_url) }}" placeholder="https://school.example.com">
                    <div class="hint">Public API root and /login redirect here (web landing + sign-in).</div>
                </div>

                <h2 class="section-title">Android</h2>
                <div class="current-release">
                    <span>Current uploaded version:</span>
                    <span>
                        <strong>{{ $settings->android_version }}</strong>
                        <code>code {{ $settings->android_version_code }}</code>
                    </span>
                    @if ($androidApkUrl)
                        <a href="{{ $androidApkUrl }}" target="_blank" rel="noopener">Download APK</a>
                    @else
                        <span style="opacity:0.8;">No APK uploaded</span>
                    @endif
                </div>
                <div class="grid">
                    <div class="field">
                        <label for="android_version">Version name</label>
                        <input id="android_version" type="text" name="android_version" value="{{ old('android_version', $settings->android_version) }}" required>
                    </div>
                    <div class="field">
                        <label for="android_version_code">Version code</label>
                        <input id="android_version_code" type="number" name="android_version_code" min="1" value="{{ old('android_version_code', $settings->android_version_code) }}" required>
                    </div>
                </div>
                <div class="field">
                    <label for="android_apk">APK file</label>
                    <input id="android_apk" type="file" name="android_apk" accept=".apk,application/vnd.android.package-archive">
                    @if ($androidApkUrl)
                        <div class="current-file">
                            Current: <a href="{{ $androidApkUrl }}" target="_blank" rel="noopener">{{ $androidApkUrl }}</a>
                        </div>
                    @endif
                    <div class="hint">Upload a new APK to replace the current download link. API: <code>{{ url('/api/mobile/version') }}</code></div>
                </div>

                <h2 class="section-title">iOS (placeholder)</h2>
                <div class="grid">
                    <div class="field">
                        <label for="ios_version">Version name</label>
                        <input id="ios_version" type="text" name="ios_version" value="{{ old('ios_version', $settings->ios_version) }}" placeholder="1.0.0">
                    </div>
                    <div class="field">
                        <label for="ios_build_number">Build number</label>
                        <input id="ios_build_number" type="number" name="ios_build_number" min="1" value="{{ old('ios_build_number', $settings->ios_build_number) }}">
                    </div>
                </div>
                <div class="field">
                    <label for="ios_ipa">IPA file (optional)</label>
                    <input id="ios_ipa" type="file" name="ios_ipa" accept=".ipa">
                    @if ($iosIpaUrl)
                        <div class="current-file">
                            Current: <a href="{{ $iosIpaUrl }}" target="_blank" rel="noopener">{{ $iosIpaUrl }}</a>
                        </div>
                    @endif
                </div>

                <div class="field">
                    <label for="release_notes">Release notes</label>
                    <textarea id="release_notes" name="release_notes">{{ old('release_notes', $settings->release_notes) }}</textarea>
                </div>

                <button type="submit" class="button button-primary">Save release settings</button>
            </form>
        </div>
    </div>
@endsection
