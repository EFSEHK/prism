@extends('layout')

@section('title', 'EFSC-YA | School Portal')

@section('styles')
    <style>
        body {
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.16), transparent 28%),
                radial-gradient(circle at bottom right, rgba(16, 185, 129, 0.18), transparent 24%),
                var(--bg);
        }

        .page {
            max-width: 960px;
            margin: 0 auto;
            padding: 40px 20px 56px;
        }

        .hero-panel {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 28px;
            box-shadow: var(--shadow);
            padding: 40px 34px;
            backdrop-filter: blur(14px);
        }

        .brand-mark {
            width: 56px;
            height: 56px;
            display: grid;
            place-items: center;
            border-radius: 18px;
            background: linear-gradient(135deg, #2563eb, #0f766e);
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 22px;
        }

        .eyebrow {
            display: inline-flex;
            padding: 8px 12px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary);
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 16px;
        }

        .headline {
            margin: 0;
            font-size: clamp(2rem, 5vw, 3rem);
            line-height: 1.1;
            letter-spacing: -0.03em;
        }

        .subtitle {
            margin: 18px 0 0;
            font-size: 1.08rem;
            line-height: 1.75;
            color: var(--muted);
            max-width: 680px;
        }

        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-top: 32px;
        }

        .link-card {
            padding: 22px;
            border-radius: 20px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: var(--soft-surface);
        }

        .link-card strong {
            display: block;
            margin-bottom: 8px;
            font-size: 1.05rem;
        }

        .link-card p {
            margin: 0 0 16px;
            color: var(--muted);
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .link-card .button {
            width: 100%;
        }

        .muted-note {
            margin-top: 28px;
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .unavailable {
            opacity: 0.72;
            pointer-events: none;
        }
    </style>
@endsection

@section('content')
    <div class="page">
        <section class="hero-panel">
            <div class="brand-mark">EF</div>
            <div class="eyebrow">Elementary Foundation School Chakwal — Youth Academy</div>
            <h1 class="headline">EFSC-YA School Portal</h1>
            <p class="subtitle">
                A unified school management platform for staff and families. Parents can follow homework,
                marks, attendance, fees, and announcements. Staff can manage academics, attendance, assessments,
                and school communications from one place.
            </p>

            <div class="links-grid">
                <article class="link-card">
                    <strong>Web application</strong>
                    <p>Staff dashboard and learner portal in the browser.</p>
                    @if ($webAppUrl)
                        <a href="{{ $webAppUrl }}" class="button button-primary" target="_blank" rel="noopener noreferrer">
                            Open web app
                        </a>
                    @else
                        <span class="button button-secondary unavailable">Link not configured yet</span>
                    @endif
                </article>

                <article class="link-card">
                    <strong>Android mobile app</strong>
                    <p>Parent and student mobile app for phones (install APK).</p>
                    @if ($androidApkUrl)
                        <a href="{{ $androidApkUrl }}" class="button button-primary" download>
                            Download Android app
                        </a>
                    @else
                        <span class="button button-secondary unavailable">APK not uploaded yet</span>
                    @endif
                </article>

                <article class="link-card">
                    <strong>iOS mobile app</strong>
                    <p>iPhone/iPad support is planned. Not available in this release.</p>
                    <span class="button button-secondary unavailable">Coming later</span>
                </article>
            </div>

            <p class="muted-note">
                API status endpoint: <code>{{ url('/up') }}</code>.
                @if ($androidApkUrl)
                    Current Android release: <strong>{{ $settings->android_version }}</strong> (build {{ $settings->android_version_code }}).
                @endif
            </p>
        </section>
    </div>
@endsection
