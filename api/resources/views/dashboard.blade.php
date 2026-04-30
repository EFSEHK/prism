@extends('layout')

@section('title', config('app.name', 'LASK') . ' Dashboard')

@section('styles')
    <style>
        body {
            background:
                radial-gradient(circle at top, rgba(37, 99, 235, 0.12), transparent 26%),
                var(--bg);
        }

        .page {
            max-width: 1240px;
            margin: 0 auto;
            padding: 28px 20px 44px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 22px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-mark {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            border-radius: 16px;
            background: linear-gradient(135deg, #2563eb, #0f766e);
            color: #fff;
            font-weight: 700;
            letter-spacing: 0.08em;
            box-shadow: var(--shadow);
        }

        .brand-copy h1 {
            margin: 0;
            font-size: 1.2rem;
        }

        .brand-copy p {
            margin: 4px 0 0;
            color: var(--muted);
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .welcome-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 16px;
            border-radius: 14px;
            border: 1px solid var(--panel-border);
            background: var(--soft-pill);
            color: var(--text);
            font-weight: 600;
        }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(280px, 360px);
            gap: 18px;
            margin-bottom: 22px;
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 26px;
            box-shadow: var(--shadow);
        }

        .hero-panel {
            padding: 28px;
        }

        .hero-panel h2 {
            margin: 0;
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 1.08;
            letter-spacing: -0.04em;
        }

        .hero-panel p {
            margin: 14px 0 0;
            color: var(--muted);
            line-height: 1.7;
            max-width: 760px;
        }

        .quick-links {
            display: grid;
            gap: 14px;
            padding: 22px;
        }

        .quick-links h3 {
            margin: 0;
            font-size: 1.1rem;
        }

        .package-card {
            display: block;
            padding: 18px;
            border-radius: 20px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: var(--soft-surface);
            transition: 0.2s ease;
        }

        .package-card:hover {
            transform: translateY(-2px);
            border-color: rgba(37, 99, 235, 0.24);
        }

        .package-card strong {
            display: block;
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .package-card span {
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .package-card small {
            display: inline-block;
            margin-top: 10px;
            color: var(--primary);
            font-weight: 600;
        }

        .stats-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 14px;
        }

        .stats-head h3 {
            margin: 0;
            font-size: 1.25rem;
        }

        .stats-head p {
            margin: 6px 0 0;
            color: var(--muted);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .stat-card {
            padding: 22px;
            border-radius: 22px;
            border: 1px solid rgba(148, 163, 184, 0.14);
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 10px 30px rgba(148, 163, 184, 0.12);
        }

        html[data-theme='dark'] .stat-card {
            background: rgba(15, 23, 42, 0.92);
        }

        .stat-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .status-dot {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            flex: 0 0 18px;
            position: relative;
        }

        .status-dot::after {
            content: '';
            position: absolute;
            inset: 5px;
            border-radius: 999px;
            background: #fff;
            opacity: 0.9;
        }

        .status-ok {
            background: rgba(16, 185, 129, 0.24);
            color: #10b981;
        }

        .status-warning {
            background: rgba(245, 158, 11, 0.24);
            color: #f59e0b;
        }

        .status-failed {
            background: rgba(239, 68, 68, 0.24);
            color: #ef4444;
        }

        .stat-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
        }

        .stat-value {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .stat-detail {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 0.93rem;
            line-height: 1.6;
        }

        .card-link {
            display: inline-flex;
            margin-top: 10px;
            color: var(--primary);
            font-size: 0.9rem;
            font-weight: 600;
        }

        @media (max-width: 1100px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .hero {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .page {
                padding: 20px 14px 32px;
            }

            .header,
            .stats-head {
                flex-direction: column;
                align-items: flex-start;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    <div class="page">
        <header class="header">
            <div class="brand">
                <div class="brand-mark">LS</div>
                <div class="brand-copy">
                    <h1>{{ config('app.name', 'LASK') }} Dashboard</h1>
                    <p>Quick access to your observability tools and live Laravel/MySQL stats.</p>
                </div>
            </div>

            <div class="actions">
                <div class="welcome-pill">Signed in as {{ auth()->user()->name }}</div>
                <button type="button" class="button button-secondary theme-toggle" data-theme-toggle aria-label="Toggle color theme">
                    <span data-theme-toggle-label>Switch to dark</span>
                </button>
                <a href="{{ route('welcome') }}" class="button">Landing Page</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="button button-primary">Logout</button>
                </form>
            </div>
        </header>

        <section class="hero">
            <div class="panel hero-panel">
                <h2>Everything important for the backend, visible in one place.</h2>
                <p>
                    This dashboard keeps the first-level operational tools close by: review log files, inspect Telescope,
                    open the health UI, and scan core Laravel/MySQL indicators without leaving the page.
                </p>
            </div>

            <aside class="panel quick-links">
                <h3>Package shortcuts</h3>
                @foreach ($packages as $package)
                    <a href="{{ $package['url'] }}" class="package-card" target="_blank" rel="noopener noreferrer">
                        <strong>{{ $package['label'] }}</strong>
                        <span>{{ $package['description'] }}</span>
                        <small>Open package UI</small>
                    </a>
                @endforeach
            </aside>
        </section>

        <section>
            <div class="stats-head">
                <div>
                    <h3>System overview</h3>
                    <p>These cards mirror the kind of high-level checks you expect from the health dashboard.</p>
                </div>
                <a href="{{ route('dashboard.health') }}" class="button" target="_blank" rel="noopener noreferrer">Open Health View</a>
            </div>

            <div class="stats-grid">
                @foreach ($cards as $card)
                    <article class="stat-card">
                        <div class="stat-head">
                            <div class="status-dot status-{{ $card['status'] }}"></div>
                            <h4 class="stat-title">{{ $card['title'] }}</h4>
                        </div>
                        <p class="stat-value">{{ $card['value'] }}</p>
                        <p class="stat-detail">{{ $card['detail'] }}</p>

                        @if ($card['title'] === 'Currently Active Connections')
                            <a href="{{ route('health.connections') }}" class="card-link">View connections JSON</a>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    </div>
@endsection
