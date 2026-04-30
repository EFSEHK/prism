@php
    $storedResults = collect($checkResults?->storedCheckResults ?? []);
    $connectionsResult = $storedResults->first(fn ($result) => $result->label === 'Currently Active Connections');
    $otherResults = $storedResults->reject(fn ($result) => $result->label === 'Currently Active Connections')->values();
@endphp
<html lang="en" class="{{ $theme == 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ __('health::notifications.health_results') }}</title>
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    {{ $assets }}
    <style>
        .health-stack {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .health-activity-card {
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            padding: 1.5rem;
        }

        .dark .health-activity-card {
            background: #1f2937;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            border-top: 1px solid #374151;
        }

        .health-activity-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .health-activity-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
        }

        .dark .health-activity-title {
            color: #f9fafb;
        }

        .health-activity-summary {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .dark .health-activity-summary {
            color: #9ca3af;
        }

        .health-activity-live-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
            color: #2563eb;
            white-space: nowrap;
        }

        .dark .health-activity-live-value {
            color: #60a5fa;
        }

        .health-activity-toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .health-activity-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .health-activity-tab {
            appearance: none;
            border: 1px solid #d1d5db;
            border-radius: 999px;
            background: #ffffff;
            color: #374151;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.5rem 0.875rem;
            transition: all 0.15s ease-in-out;
        }

        .health-activity-tab:hover {
            border-color: #93c5fd;
            color: #1d4ed8;
        }

        .health-activity-tab[aria-pressed="true"] {
            border-color: #2563eb;
            background: #2563eb;
            color: #ffffff;
        }

        .dark .health-activity-tab {
            background: #111827;
            border-color: #4b5563;
            color: #e5e7eb;
        }

        .dark .health-activity-tab:hover {
            border-color: #60a5fa;
            color: #bfdbfe;
        }

        .dark .health-activity-tab[aria-pressed="true"] {
            border-color: #60a5fa;
            background: #1d4ed8;
            color: #ffffff;
        }

        .health-activity-toggle {
            appearance: none;
            border: 1px solid #d1d5db;
            border-radius: 0.75rem;
            background: transparent;
            color: #374151;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.5rem 0.875rem;
        }

        .dark .health-activity-toggle {
            border-color: #4b5563;
            color: #e5e7eb;
        }

        .health-activity-chart {
            width: 100%;
            height: 260px;
            border-radius: 1rem;
            background: linear-gradient(180deg, rgba(37, 99, 235, 0.08) 0%, rgba(37, 99, 235, 0.02) 100%);
            border: 1px solid rgba(148, 163, 184, 0.25);
            overflow: hidden;
            position: relative;
        }

        .dark .health-activity-chart {
            background: linear-gradient(180deg, rgba(96, 165, 250, 0.12) 0%, rgba(96, 165, 250, 0.04) 100%);
            border-color: rgba(75, 85, 99, 0.75);
        }

        .health-activity-chart svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .health-activity-series-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 0.875rem;
        }

        .health-activity-series-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #374151;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .dark .health-activity-series-item {
            color: #e5e7eb;
        }

        .health-activity-series-dot {
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 999px;
            display: inline-block;
        }

        .health-activity-series-dot.connections {
            background: #2563eb;
        }

        .health-activity-series-dot.users {
            background: #10b981;
        }

        .health-activity-legend {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            margin-top: 0.75rem;
            color: #6b7280;
            font-size: 0.75rem;
        }

        .dark .health-activity-legend {
            color: #9ca3af;
        }

        .health-activity-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.1);
            color: #1d4ed8;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.375rem 0.625rem;
        }

        .dark .health-activity-pill {
            background: rgba(96, 165, 250, 0.15);
            color: #bfdbfe;
        }

        .health-activity-empty {
            display: flex;
            height: 100%;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .health-activity-data {
            display: none;
            margin-top: 1rem;
            overflow-x: auto;
        }

        .health-activity-data.is-visible {
            display: block;
        }

        .health-activity-table {
            width: 100%;
            border-collapse: collapse;
            color: #374151;
            font-size: 0.875rem;
        }

        .dark .health-activity-table {
            color: #e5e7eb;
        }

        .health-activity-table th,
        .health-activity-table td {
            padding: 0.625rem 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .dark .health-activity-table th,
        .dark .health-activity-table td {
            border-bottom-color: #374151;
        }

        @media (max-width: 768px) {
            .health-activity-card {
                padding: 1rem;
            }

            .health-activity-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .health-activity-live-value {
                font-size: 1.5rem;
            }

            .health-activity-chart {
                height: 220px;
            }
        }
    </style>
</head>

<body class="antialiased bg-gray-100 mt-7 md:mt-12 dark:bg-gray-900">
    <div class="mx-auto max-w-7xl lg:px-8 sm:px-6">
        <div class="flex flex-wrap justify-center space-y-3">
            <h4 class="w-full text-2xl font-bold text-center text-gray-900 dark:text-white">{{ __('health::notifications.laravel_health') }}</h4>
            <div class="flex justify-center w-full">
                <x-health-logo/>
            </div>
            @if ($lastRanAt)
                <div class="{{ $lastRanAt->diffInMinutes() > 5 ? 'text-red-400' : 'text-gray-400 dark:text-gray-500' }} text-sm text-center font-medium">
                    {{ __('health::notifications.check_results_from') }} {{ $lastRanAt->diffForHumans() }}
                </div>
            @endif
        </div>
        <div class="px-2 my-6 md:mt-8 md:px-0">
            <div class="health-stack">
                @if ($otherResults->count())
                    <dl class="grid grid-cols-1 gap-2.5 sm:gap-3 md:gap-5 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($otherResults as $result)
                            <div class="flex items-start px-4 space-x-2 overflow-hidden py-5 text-opacity-0 transition transform bg-white shadow-md shadow-gray-200 dark:shadow-black/25 dark:shadow-md dark:bg-gray-800 rounded-xl sm:p-6 md:space-x-3 md:min-h-[130px] dark:border-t dark:border-gray-700">
                                <x-health-status-indicator :result="$result" />
                                <div>
                                    <dd class="-mt-1 font-bold text-gray-900 dark:text-white md:mt-1 md:text-xl">
                                        {{ $result->label }}
                                    </dd>
                                    <dt class="mt-0 text-sm font-medium text-gray-600 dark:text-gray-300 md:mt-1">
                                        @if (!empty($result->notificationMessage))
                                            {{ $result->notificationMessage }}
                                        @else
                                            {{ $result->shortSummary }}
                                        @endif
                                    </dt>
                                </div>
                            </div>
                        @endforeach
                    </dl>
                @endif

                @if ($connectionsResult)
                    <section
                        id="health-activity-panel"
                        class="health-activity-card"
                        data-endpoint="{{ route('dashboard.health.metrics') }}"
                        data-initial-range="1m"
                    >
                        <div class="health-activity-header">
                            <div class="flex items-start space-x-2 md:space-x-3">
                                <x-health-status-indicator :result="$connectionsResult" />
                                <div>
                                    <div class="health-activity-title">Currently Active Connections</div>
                                    <div class="health-activity-summary" data-role="updated-at">
                                        Updates every 5 seconds
                                    </div>
                                </div>
                            </div>
                            <div class="health-activity-live-value" data-role="current-connections">--</div>
                        </div>

                        <div class="health-activity-toolbar">
                            <div class="health-activity-tabs" data-role="tabs" role="tablist" aria-label="Connection history ranges">
                                <button type="button" class="health-activity-tab" data-range="1m" aria-pressed="true">1 minute</button>
                                <button type="button" class="health-activity-tab" data-range="10m" aria-pressed="false">10 minutes</button>
                                <button type="button" class="health-activity-tab" data-range="30m" aria-pressed="false">30 minutes</button>
                                <button type="button" class="health-activity-tab" data-range="1h" aria-pressed="false">1 hour</button>
                            </div>
                            <button type="button" class="health-activity-toggle" data-role="toggle-table">View graph data</button>
                        </div>

                        <div class="health-activity-chart" data-role="chart" aria-live="polite"></div>

                        <div class="health-activity-series-legend">
                            <span class="health-activity-series-item">
                                <span class="health-activity-series-dot connections"></span>
                                Active connections
                            </span>
                            <span class="health-activity-series-item">
                                <span class="health-activity-series-dot users"></span>
                                Active users
                            </span>
                        </div>

                        <div class="health-activity-legend">
                            <span class="health-activity-pill" data-role="range-label">12 nodes, sampled every 5 seconds</span>
                            <span data-role="summary">Waiting for the latest sample...</span>
                        </div>

                        <div id="health-activity-data" class="health-activity-data">
                            <table class="health-activity-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Time</th>
                                        <th scope="col">Connections</th>
                                        <th scope="col">Active users</th>
                                    </tr>
                                </thead>
                                <tbody data-role="table-body">
                                    <tr>
                                        <td colspan="3">Waiting for the latest sample...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif
            </div>
        </div>
    </div>

    @if ($connectionsResult)
        <script>
            (() => {
                const panel = document.getElementById('health-activity-panel');

                if (!panel) {
                    return;
                }

                const endpoint = panel.dataset.endpoint;
                const chartRoot = panel.querySelector('[data-role="chart"]');
                const currentConnectionsRoot = panel.querySelector('[data-role="current-connections"]');
                const updatedAtRoot = panel.querySelector('[data-role="updated-at"]');
                const rangeLabelRoot = panel.querySelector('[data-role="range-label"]');
                const summaryRoot = panel.querySelector('[data-role="summary"]');
                const tableBody = panel.querySelector('[data-role="table-body"]');
                const toggleButton = panel.querySelector('[data-role="toggle-table"]');
                const tablePanel = document.getElementById('health-activity-data');
                const tabs = Array.from(panel.querySelectorAll('[data-range]'));
                const refreshIntervalMs = 5000;
                const rangeMeta = {
                    '1m': '12 nodes, sampled every 5 seconds',
                    '10m': '10 nodes, sampled every minute',
                    '30m': '6 nodes, sampled every 5 minutes',
                    '1h': '12 nodes, sampled every 5 minutes',
                };
                let selectedRange = panel.dataset.initialRange || '1m';
                let refreshHandle = null;
                let lastPayload = null;
                let loading = false;

                const escapeHtml = (value) => String(value)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');

                const formatMetric = (value) => value === null || value === undefined ? '—' : String(value);

                const formatTimestamp = (value, options = {}) => {
                    if (!value) {
                        return '—';
                    }

                    return new Intl.DateTimeFormat(undefined, {
                        year: 'numeric',
                        month: 'short',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        ...options,
                    }).format(new Date(value));
                };

                const setSelectedRange = (range) => {
                    selectedRange = range;

                    tabs.forEach((button) => {
                        button.setAttribute('aria-pressed', button.dataset.range === range ? 'true' : 'false');
                    });
                };

                const renderChart = (points) => {
                    const width = 860;
                    const height = 260;
                    const padding = 24;
                    const validPoints = points
                        .map((point, index) => ({
                            label: point.bucket_label,
                            connections: point.connections,
                            activeUsers: point.active_users,
                            index,
                        }))
                        .filter((point) => point.connections !== null || point.activeUsers !== null);

                    if (!validPoints.length) {
                        chartRoot.innerHTML = '<div class="health-activity-empty">Graph data will appear as samples are collected.</div>';
                        return;
                    }

                    const counts = validPoints.flatMap((point) => [
                        point.connections,
                        point.activeUsers,
                    ]).filter((value) => value !== null);
                    const minCount = 0;
                    const maxCount = Math.max(...counts, 1);
                    const safeRange = Math.max(maxCount - minCount, 1);
                    const xForIndex = (index) => padding + ((width - (padding * 2)) * index / Math.max(points.length - 1, 1));
                    const yForCount = (count) => (height - padding) - (((count - minCount) / safeRange) * (height - (padding * 2)));
                    const yLabels = Array.from({ length: 4 }, (_, step) => {
                        const ratio = step / 3;
                        const value = Math.round(maxCount - ((maxCount - minCount) * ratio));
                        const y = padding + ((height - (padding * 2)) * ratio);

                        return { value, y };
                    });

                    const buildSegments = (seriesKey) => {
                        const segments = [];
                        let currentSegment = [];

                        points.forEach((point, index) => {
                            const value = seriesKey === 'activeUsers' ? point.active_users : point.connections;

                            if (value === null) {
                                if (currentSegment.length) {
                                    segments.push(currentSegment);
                                    currentSegment = [];
                                }

                                return;
                            }

                            currentSegment.push(`${xForIndex(index)},${yForCount(value)}`);
                        });

                        if (currentSegment.length) {
                            segments.push(currentSegment);
                        }

                        return segments;
                    };

                    const buildDots = (seriesKey, color, label) => points
                        .map((point, index) => ({
                            label: point.bucket_label,
                            value: seriesKey === 'activeUsers' ? point.active_users : point.connections,
                            index,
                        }))
                        .filter((point) => point.value !== null)
                        .map((point) => {
                            const x = xForIndex(point.index);
                            const y = yForCount(point.value);

                            return `
                                <circle cx="${x}" cy="${y}" r="4" fill="${color}"></circle>
                                <title>${escapeHtml(point.label)}: ${escapeHtml(label)} ${escapeHtml(point.value)}</title>
                            `;
                        }).join('');

                    const xLabels = points.map((point, index) => {
                        const x = xForIndex(index);

                        return `
                            <text x="${x}" y="${height - 6}" text-anchor="middle" fill="#6b7280" font-size="11">
                                ${escapeHtml(point.bucket_label)}
                            </text>
                        `;
                    }).join('');

                    const yAxis = yLabels.map((label) => `
                        <g>
                            <line x1="${padding}" y1="${label.y}" x2="${width - padding}" y2="${label.y}" stroke="rgba(148,163,184,0.25)" stroke-dasharray="4 4"></line>
                            <text x="${padding - 8}" y="${label.y + 4}" text-anchor="end" fill="#6b7280" font-size="11">
                                ${escapeHtml(label.value)}
                            </text>
                        </g>
                    `).join('');

                    const renderLines = (segments, color) => segments.map((segment) => `
                        <polyline
                            fill="none"
                            stroke="${color}"
                            stroke-width="3"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            points="${segment.join(' ')}"
                        ></polyline>
                    `).join('');

                    const connectionSegments = buildSegments('connections');
                    const activeUserSegments = buildSegments('activeUsers');
                    const lines = renderLines(connectionSegments, '#2563eb') + renderLines(activeUserSegments, '#10b981');
                    const dots = buildDots('connections', '#2563eb', 'Connections:') + buildDots('activeUsers', '#10b981', 'Active users:');

                    chartRoot.innerHTML = `
                        <svg viewBox="0 0 ${width} ${height}" preserveAspectRatio="none" role="img" aria-label="Database active connections graph">
                            ${yAxis}
                            ${lines}
                            ${dots}
                            ${xLabels}
                        </svg>
                    `;
                };

                const renderTable = (points) => {
                    const rows = [...points].reverse();

                    if (rows.length === 0) {
                        tableBody.innerHTML = '<tr class="health-activity-table-empty"><td colspan="3">No graph data available.</td></tr>';
                        return;
                    }

                    tableBody.innerHTML = rows.map((point) => `
                        <tr>
                            <td>${escapeHtml(formatTimestamp(point.bucket_started_at))}</td>
                            <td>${point.connections === null ? 'No sample yet' : escapeHtml(formatMetric(point.connections))}</td>
                            <td>${point.active_users === null ? 'No sample yet' : escapeHtml(formatMetric(point.active_users))}</td>
                        </tr>
                    `).join('');
                };

                const applyPayload = (payload) => {
                    lastPayload = payload;
                    currentConnectionsRoot.textContent = formatMetric(payload.current?.connections);
                    updatedAtRoot.textContent = `Last updated ${formatTimestamp(payload.current?.sampled_at, { hour: '2-digit', minute: '2-digit', second: '2-digit' })} · active users ${formatMetric(payload.current?.active_users)} · auto refresh every 5 seconds`;
                    rangeLabelRoot.textContent = rangeMeta[selectedRange] ?? '';
                    const sampledPoints = (payload.points || []).filter((point) => point.connections !== null || point.active_users !== null);
                    summaryRoot.textContent = sampledPoints.length
                        ? `Showing ${sampledPoints.length} sampled point${sampledPoints.length === 1 ? '' : 's'} in this range`
                        : 'Waiting for enough samples to draw the graph';
                    renderChart(payload.points || []);
                    renderTable(payload.points || []);
                };

                const loadMetrics = async () => {
                    if (loading) {
                        return;
                    }

                    loading = true;
                    summaryRoot.textContent = 'Refreshing active connection history...';

                    try {
                        const response = await fetch(`${endpoint}?range=${encodeURIComponent(selectedRange)}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            cache: 'no-store',
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }

                        const payload = await response.json();
                        applyPayload(payload);
                    } catch (error) {
                        if (!lastPayload) {
                            chartRoot.innerHTML = '<div class="health-activity-empty">Unable to load graph data right now.</div>';
                        }

                        summaryRoot.textContent = 'Unable to refresh the active connection history right now.';
                    } finally {
                        loading = false;
                        window.clearTimeout(refreshHandle);
                        refreshHandle = window.setTimeout(loadMetrics, refreshIntervalMs);
                    }
                };

                tabs.forEach((button) => {
                    button.addEventListener('click', () => {
                        if (button.dataset.range === selectedRange) {
                            return;
                        }

                        setSelectedRange(button.dataset.range);
                        loadMetrics();
                    });
                });

                toggleButton.addEventListener('click', () => {
                    const isVisible = tablePanel.classList.toggle('is-visible');
                    toggleButton.textContent = isVisible ? 'Hide graph data' : 'View graph data';
                });

                window.addEventListener('resize', () => {
                    if (lastPayload) {
                        renderChart(lastPayload.points || []);
                    }
                });

                setSelectedRange(selectedRange);
                loadMetrics();
            })();
        </script>
    @endif
</body>
</html>
