@extends('layout')

@section('title', config('app.name').' — Local API')

@section('styles')
    <style>
        body { background: var(--bg); }
        .page {
            max-width: 720px;
            margin: 0 auto;
            padding: 48px 20px 64px;
        }
        .panel {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 24px;
            padding: 32px;
            box-shadow: var(--shadow);
        }
        .eyebrow {
            display: inline-flex;
            padding: 6px 12px;
            border-radius: 999px;
            background: var(--soft-badge);
            color: var(--muted);
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 16px;
        }
        h1 { margin: 0 0 10px; font-size: 1.85rem; }
        .lead {
            margin: 0 0 28px;
            color: var(--muted);
            line-height: 1.55;
            font-size: 1.02rem;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 28px;
        }
        .meta {
            display: grid;
            gap: 12px;
            padding-top: 8px;
            border-top: 1px solid var(--panel-border);
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            font-size: 0.92rem;
        }
        .meta-row span { color: var(--muted); }
        .meta-row a { color: var(--primary); text-decoration: underline; }
        .hint {
            margin-top: 20px;
            color: var(--muted);
            font-size: 0.88rem;
            line-height: 1.5;
        }
    </style>
@endsection

@section('content')
    <div class="page">
        <div class="panel">
            <div class="eyebrow">Local development</div>
            <h1>{{ config('app.name') }} API</h1>
            <p class="lead">
                You are on the local API host. Production redirects to the live web app;
                locally this landing page stays here so you can reach the API and tools.
            </p>

            <div class="actions">
                <a class="button button-primary" href="{{ $webAppUrl }}">Open web app</a>
                @if ($apkUrl)
                    <a class="button button-secondary" href="{{ $apkUrl }}">Download Android APK</a>
                @endif
                <a class="button button-secondary" href="{{ url('/api/mobile/version') }}">Mobile version JSON</a>
            </div>

            <div class="meta">
                <div class="meta-row">
                    <span>API base</span>
                    <code>{{ url('/api') }}</code>
                </div>
                <div class="meta-row">
                    <span>Web app</span>
                    <a href="{{ $webAppUrl }}">{{ $webAppUrl }}</a>
                </div>
                <div class="meta-row">
                    <span>Android version</span>
                    <code>{{ $settings->android_version }} (code {{ $settings->android_version_code }})</code>
                </div>
            </div>

            <p class="hint">
                Tip: use <code>{{ url('/api') }}/…</code> for API calls. Dev portal:
                <a href="{{ url('/'.trim(config('releases.dev_portal_path'), '/')) }}">
                    /{{ trim(config('releases.dev_portal_path'), '/') }}
                </a>
            </p>
        </div>
    </div>
@endsection
