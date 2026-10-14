@extends('layouts.app')

@section('title', 'Analytics Dashboard - Kitchen Performance & Event Pipeline')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Dashboard Header & Date Filters -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8 bg-white p-6 rounded-3xl border border-stone-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-bold uppercase tracking-wider text-stone-500">Live Ingestion & Storage</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-stone-900 tracking-tight">Analytics & Intelligence</h1>
            <p class="text-xs sm:text-sm text-stone-500 mt-1">Cross-platform telemetry from Browser SDK and Server Helper</p>
        </div>

        <!-- Date Range Filter Controls -->
        <div class="flex flex-wrap items-center gap-2">
            <div class="bg-stone-100 p-1 rounded-xl flex items-center gap-1 border border-stone-200">
                <button onclick="setDatePreset('today')" id="btn-filter-today" class="date-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg text-stone-600 hover:text-stone-900 transition-colors">Today</button>
                <button onclick="setDatePreset('7days')" id="btn-filter-7days" class="date-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg bg-white text-orange-600 shadow-xs transition-colors">Last 7 Days</button>
                <button onclick="setDatePreset('30days')" id="btn-filter-30days" class="date-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg text-stone-600 hover:text-stone-900 transition-colors">Last 30 Days</button>
            </div>

            <div class="flex items-center gap-1.5 bg-stone-50 p-1 rounded-xl border border-stone-200">
                <input type="date" id="filter-from" class="text-xs px-2 py-1 bg-white border border-stone-200 rounded-lg text-stone-700">
                <span class="text-xs text-stone-400">to</span>
                <input type="date" id="filter-to" class="text-xs px-2 py-1 bg-white border border-stone-200 rounded-lg text-stone-700">
                <button onclick="applyCustomDateRange()" class="px-3 py-1.5 bg-stone-900 hover:bg-stone-800 text-white rounded-lg text-xs font-bold transition-colors">
                    Filter
                </button>
            </div>

            <!-- Auto-refresh Toggle -->
            <button onclick="toggleAutoRefresh()" id="btn-auto-refresh" class="px-3 py-2 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-xl text-xs font-semibold flex items-center gap-2 border border-stone-200 transition-colors">
                <i class="fa-solid fa-arrows-rotate text-emerald-600" id="refresh-icon"></i>
                <span id="refresh-label">Live: On (10s)</span>
            </button>
        </div>
    </div>

    <!-- 1. KPI Summary Cards Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <!-- Pageviews -->
        <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
            <div class="flex items-center justify-between text-stone-400 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-stone-500">Page Views</span>
                <i class="fa-regular fa-eye text-orange-500"></i>
            </div>
            <div class="text-2xl font-black text-stone-900" id="kpi-pageviews">--</div>
            <span class="text-[11px] text-stone-400">Total visits recorded</span>
        </div>

        <!-- Unique Visitors -->
        <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
            <div class="flex items-center justify-between text-stone-400 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-stone-500">Unique Visitors</span>
                <i class="fa-regular fa-user text-sky-500"></i>
            </div>
            <div class="text-2xl font-black text-stone-900" id="kpi-visitors">--</div>
            <span class="text-[11px] text-stone-400">Distinct anonymous IDs</span>
        </div>

        <!-- Sessions -->
        <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
            <div class="flex items-center justify-between text-stone-400 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-stone-500">Sessions</span>
                <i class="fa-solid fa-layer-group text-purple-500"></i>
            </div>
            <div class="text-2xl font-black text-stone-900" id="kpi-sessions">--</div>
            <span class="text-[11px] text-stone-400">30-min window sessions</span>
        </div>

        <!-- Total Orders (Server tracked) -->
        <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
            <div class="flex items-center justify-between text-stone-400 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-stone-500">Orders Created</span>
                <i class="fa-solid fa-bag-shopping text-emerald-500"></i>
            </div>
            <div class="text-2xl font-black text-emerald-600" id="kpi-orders">--</div>
            <span class="text-[11px] text-emerald-600 font-medium">Server SDK tracked</span>
        </div>

        <!-- Conversion Rate -->
        <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
            <div class="flex items-center justify-between text-stone-400 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-stone-500">Conversion Rate</span>
                <i class="fa-solid fa-arrow-trend-up text-amber-500"></i>
            </div>
            <div class="text-2xl font-black text-stone-900" id="kpi-conversion">--%</div>
            <span class="text-[11px] text-stone-400">Orders / Visitors</span>
        </div>

        <!-- Total Revenue -->
        <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
            <div class="flex items-center justify-between text-stone-400 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-stone-500">Gross Sales</span>
                <i class="fa-solid fa-dollar-sign text-emerald-500"></i>
            </div>
            <div class="text-2xl font-black text-stone-900" id="kpi-revenue">$0.00</div>
            <span class="text-[11px] text-stone-400">From completed orders</span>
        </div>
    </div>

    <!-- 2. Charts Section (Time Series & Popular Pages) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Main Line Chart: Traffic & Orders Over Time -->
        <div class="lg:col-span-2 bg-white p-6 rounded-3xl border border-stone-200 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-stone-900">Traffic & Conversion Trend</h3>
                    <p class="text-xs text-stone-500">Daily Page Views and Unique Visitors vs Orders</p>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-orange-500"></span> Pageviews</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-sky-500"></span> Visitors</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500"></span> Orders</span>
                </div>
            </div>
            <div class="h-72">
                <canvas id="chart-traffic"></canvas>
            </div>
        </div>

        <!-- Doughnut Chart: Event Distribution & Sources -->
        <div class="bg-white p-6 rounded-3xl border border-stone-200 shadow-xs flex flex-col justify-between">
            <div>
                <h3 class="text-base font-bold text-stone-900 mb-1">Event Type Distribution</h3>
                <p class="text-xs text-stone-500 mb-4">Volume breakdown by event tag</p>
                <div class="h-56 relative flex items-center justify-center">
                    <canvas id="chart-events"></canvas>
                </div>
            </div>
            <div id="events-legend" class="mt-4 pt-4 border-t border-stone-100 grid grid-cols-2 gap-2 text-xs"></div>
        </div>
    </div>

    <!-- 3. Conversion Funnel & Popular Pages Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Step-by-Step Conversion Funnel -->
        <div class="bg-white p-6 rounded-3xl border border-stone-200 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-stone-900">Customer Conversion Funnel</h3>
                    <p class="text-xs text-stone-500">Drop-off rate across ordering stages</p>
                </div>
                <span class="text-xs font-semibold text-orange-600 bg-orange-50 px-2.5 py-1 rounded-full">End-to-End Flow</span>
            </div>
            
            <div id="funnel-container" class="space-y-4">
                <!-- Dynamically rendered -->
            </div>
        </div>

        <!-- Top Popular Pages / URLs -->
        <div class="bg-white p-6 rounded-3xl border border-stone-200 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-stone-900">Popular Paths & Pages</h3>
                    <p class="text-xs text-stone-500">Top visited URLs tracked by Browser SDK</p>
                </div>
                <i class="fa-solid fa-compass text-stone-400"></i>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-stone-100 text-stone-400 uppercase tracking-wider font-semibold">
                            <th class="pb-3">Path</th>
                            <th class="pb-3 text-right">Views</th>
                            <th class="pb-3 text-right">Unique Users</th>
                            <th class="pb-3 text-right">Share</th>
                        </tr>
                    </thead>
                    <tbody id="popular-pages-tbody" class="divide-y divide-stone-100 text-stone-700">
                        <!-- Dynamically populated -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 4. Real-time Live Event Activity Log Stream -->
    <div class="bg-white rounded-3xl border border-stone-200 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-stone-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-bold text-stone-900">Raw Event Ingestion Stream</h3>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-stone-100 text-stone-600 border border-stone-200">Last 25 Events</span>
                </div>
                <p class="text-xs text-stone-500 mt-0.5">Real-time incoming telemetry stream from browser and server helpers</p>
            </div>
            <button onclick="fetchStats()" class="px-3 py-1.5 text-xs font-semibold bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-xl transition-colors self-start sm:self-auto flex items-center gap-1.5">
                <i class="fa-solid fa-arrows-rotate"></i> Refresh Feed
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-stone-50 text-stone-500 uppercase tracking-wider font-bold border-b border-stone-200">
                    <tr>
                        <th class="py-3 px-4">Timestamp</th>
                        <th class="py-3 px-4">Event Name</th>
                        <th class="py-3 px-4">Source</th>
                        <th class="py-3 px-4">Path / Location</th>
                        <th class="py-3 px-4">Visitor / Session ID</th>
                        <th class="py-3 px-4 text-right">Payload</th>
                    </tr>
                </thead>
                <tbody id="recent-events-tbody" class="divide-y divide-stone-100">
                    <tr>
                        <td colspan="6" class="py-8 text-center text-stone-400">Loading events...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- Event Payload Inspector Modal -->
<!-- ============================================== -->
<div id="inspector-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs opacity-0 pointer-events-none transition-opacity duration-300">
    <div class="bg-stone-950 text-stone-100 rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-stone-800 transform scale-95 transition-transform duration-300">
        <div class="flex items-center justify-between pb-4 border-b border-stone-800">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-code text-orange-400"></i>
                <h3 class="text-sm font-bold text-white" id="inspector-title">Event Telemetry Inspector</h3>
            </div>
            <button onclick="closeInspector()" class="w-8 h-8 rounded-lg bg-stone-800 hover:bg-stone-700 text-stone-400 hover:text-white flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="py-4 space-y-4">
            <div>
                <label class="text-[11px] font-bold text-stone-400 uppercase tracking-wider block mb-1">Custom Business Properties (`properties`)</label>
                <pre id="inspector-properties" class="bg-stone-900 p-3 rounded-xl text-xs text-orange-300 font-mono overflow-x-auto max-h-48 border border-stone-800/80"></pre>
            </div>
            <div>
                <label class="text-[11px] font-bold text-stone-400 uppercase tracking-wider block mb-1">Device & Client Context (`context`)</label>
                <pre id="inspector-context" class="bg-stone-900 p-3 rounded-xl text-xs text-sky-300 font-mono overflow-x-auto max-h-48 border border-stone-800/80"></pre>
            </div>
        </div>

        <div class="flex justify-end pt-2 border-t border-stone-800">
            <button onclick="closeInspector()" class="px-4 py-2 bg-stone-800 hover:bg-stone-700 text-stone-200 rounded-xl text-xs font-semibold transition-colors">
                Close Inspector
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // State
    let currentFromDate = '';
    let currentToDate = '';
    let trafficChartInstance = null;
    let eventsChartInstance = null;
    let autoRefreshTimer = null;
    let isAutoRefreshing = true;
    let recentEventsData = [];

    // Initialize Dates & Listeners
    document.addEventListener('DOMContentLoaded', () => {
        setDatePreset('7days');
        startAutoRefresh();
    });

    window.refreshDashboard = function() {
        fetchStats();
    };

    function setDatePreset(preset) {
        const to = new Date();
        let from = new Date();

        document.querySelectorAll('.date-filter-btn').forEach(b => {
            b.classList.remove('bg-white', 'text-orange-600', 'shadow-xs');
            b.classList.add('text-stone-600');
        });

        if (preset === 'today') {
            document.getElementById('btn-filter-today').classList.add('bg-white', 'text-orange-600', 'shadow-xs');
            document.getElementById('btn-filter-today').classList.remove('text-stone-600');
        } else if (preset === '7days') {
            from.setDate(to.getDate() - 6);
            document.getElementById('btn-filter-7days').classList.add('bg-white', 'text-orange-600', 'shadow-xs');
            document.getElementById('btn-filter-7days').classList.remove('text-stone-600');
        } else if (preset === '30days') {
            from.setDate(to.getDate() - 29);
            document.getElementById('btn-filter-30days').classList.add('bg-white', 'text-orange-600', 'shadow-xs');
            document.getElementById('btn-filter-30days').classList.remove('text-stone-600');
        }

        currentFromDate = from.toISOString().split('T')[0];
        currentToDate = to.toISOString().split('T')[0];

        document.getElementById('filter-from').value = currentFromDate;
        document.getElementById('filter-to').value = currentToDate;

        fetchStats();
    }

    function applyCustomDateRange() {
        currentFromDate = document.getElementById('filter-from').value;
        currentToDate = document.getElementById('filter-to').value;

        document.querySelectorAll('.date-filter-btn').forEach(b => {
            b.classList.remove('bg-white', 'text-orange-600', 'shadow-xs');
            b.classList.add('text-stone-600');
        });

        fetchStats();
    }

    async function fetchStats() {
        try {
            const url = `/api/v1/analytics/stats?from=${currentFromDate}&to=${currentToDate}`;
            const res = await fetch(url);
            if (!res.ok) throw new Error('API status ' + res.status);
            const data = await res.json();

            renderKPIs(data.summary);
            renderTrafficChart(data.traffic_over_time);
            renderEventsChart(data.top_events);
            renderFunnel(data.funnel);
            renderPopularPages(data.popular_pages);
            renderRecentEvents(data.recent_events);
        } catch (err) {
            console.error('Failed to load stats:', err);
        }
    }

    // 1. Render KPIs
    function renderKPIs(summary) {
        document.getElementById('kpi-pageviews').textContent = summary.page_views.toLocaleString();
        document.getElementById('kpi-visitors').textContent = summary.unique_visitors.toLocaleString();
        document.getElementById('kpi-sessions').textContent = summary.total_sessions.toLocaleString();
        document.getElementById('kpi-orders').textContent = summary.total_orders.toLocaleString();
        document.getElementById('kpi-conversion').textContent = summary.conversion_rate + '%';
        document.getElementById('kpi-revenue').textContent = '$' + summary.total_revenue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // 2. Render Traffic & Orders Line Chart
    function renderTrafficChart(trafficData) {
        const ctx = document.getElementById('chart-traffic').getContext('2d');
        const labels = trafficData.map(d => d.date);
        const pageviews = trafficData.map(d => d.pageviews);
        const visitors = trafficData.map(d => d.visitors);
        const orders = trafficData.map(d => d.orders);

        if (trafficChartInstance) {
            trafficChartInstance.destroy();
        }

        trafficChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Page Views',
                        data: pageviews,
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.08)',
                        tension: 0.35,
                        fill: true,
                        borderWidth: 2.5,
                        pointRadius: 4,
                        pointBackgroundColor: '#f97316',
                    },
                    {
                        label: 'Unique Visitors',
                        data: visitors,
                        borderColor: '#0ea5e9',
                        backgroundColor: 'transparent',
                        tension: 0.35,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#0ea5e9',
                    },
                    {
                        label: 'Orders Placed',
                        data: orders,
                        borderColor: '#10b981',
                        backgroundColor: 'transparent',
                        tension: 0.35,
                        borderWidth: 2.5,
                        pointRadius: 4,
                        pointBackgroundColor: '#10b981',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        padding: 10,
                        cornerRadius: 8,
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { precision: 0, font: { size: 11 } }
                    }
                }
            }
        });
    }

    // 3. Render Events Doughnut Chart
    function renderEventsChart(topEvents) {
        const ctx = document.getElementById('chart-events').getContext('2d');
        const labels = topEvents.map(e => e.event_name);
        const counts = topEvents.map(e => e.count);
        const colors = [
            '#f97316', '#0ea5e9', '#10b981', '#8b5cf6', 
            '#ec4899', '#f59e0b', '#6366f1', '#14b8a6'
        ];

        if (eventsChartInstance) {
            eventsChartInstance.destroy();
        }

        eventsChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                cutout: '65%'
            }
        });

        // Legend list
        const legendContainer = document.getElementById('events-legend');
        let legendHtml = '';
        topEvents.forEach((ev, idx) => {
            const color = colors[idx % colors.length];
            const badge = ev.event_source === 'server' ? 'bg-purple-100 text-purple-700' : 'bg-sky-100 text-sky-700';
            legendHtml += `
                <div class="flex items-center justify-between text-[11px] truncate">
                    <span class="flex items-center gap-1.5 truncate">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: ${color}"></span>
                        <span class="truncate font-medium text-stone-700">${ev.event_name}</span>
                    </span>
                    <span class="font-bold text-stone-900">${ev.count}</span>
                </div>
            `;
        });
        legendContainer.innerHTML = legendHtml;
    }

    // 4. Render Funnel
    function renderFunnel(funnel) {
        const container = document.getElementById('funnel-container');
        let html = '';

        funnel.forEach((item, index) => {
            const width = Math.max(item.conversion_pct, 4);
            const colorClass = index === funnel.length - 1 ? 'bg-emerald-500' : 'bg-orange-500';
            html += `
                <div>
                    <div class="flex justify-between items-center text-xs mb-1.5 font-semibold">
                        <span class="text-stone-800">${item.step}</span>
                        <div class="flex items-center gap-2">
                            <span class="text-stone-500">${item.unique_users} visitors</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${index === 0 ? 'bg-stone-100 text-stone-600' : 'bg-orange-100 text-orange-700'}">
                                ${item.conversion_pct}%
                            </span>
                        </div>
                    </div>
                    <div class="w-full bg-stone-100 h-2.5 rounded-full overflow-hidden">
                        <div class="${colorClass} h-full rounded-full transition-all duration-500" style="width: ${width}%"></div>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    // 5. Render Popular Pages
    function renderPopularPages(popularPages) {
        const tbody = document.getElementById('popular-pages-tbody');
        if (!popularPages || popularPages.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="py-4 text-center text-stone-400">No page view data yet</td></tr>`;
            return;
        }

        const totalViews = popularPages.reduce((sum, p) => sum + p.views, 0);

        let html = '';
        popularPages.forEach(p => {
            const share = totalViews > 0 ? ((p.views / totalViews) * 100).toFixed(1) : 0;
            html += `
                <tr class="hover:bg-stone-50/80 transition-colors">
                    <td class="py-2.5 font-mono font-medium text-stone-800">${p.path}</td>
                    <td class="py-2.5 text-right font-bold text-stone-900">${p.views.toLocaleString()}</td>
                    <td class="py-2.5 text-right text-stone-600">${p.visitors.toLocaleString()}</td>
                    <td class="py-2.5 text-right font-semibold text-orange-600">${share}%</td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }

    // 6. Render Recent Events Table
    function renderRecentEvents(events) {
        recentEventsData = events;
        const tbody = document.getElementById('recent-events-tbody');
        if (!events || events.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="py-8 text-center text-stone-400">No events captured in this date window.</td></tr>`;
            return;
        }

        let html = '';
        events.forEach((ev, idx) => {
            const sourceBadge = ev.event_source === 'server'
                ? '<span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-100 text-purple-700 border border-purple-200"><i class="fa-solid fa-server mr-1"></i>SERVER</span>'
                : '<span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-sky-100 text-sky-700 border border-sky-200"><i class="fa-solid fa-globe mr-1"></i>BROWSER</span>';

            const formattedTime = new Date(ev.occurred_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });

            html += `
                <tr class="hover:bg-stone-50 transition-colors">
                    <td class="py-3 px-4 font-mono text-stone-500 whitespace-nowrap">${formattedTime}</td>
                    <td class="py-3 px-4 font-bold text-stone-900">${ev.event_name}</td>
                    <td class="py-3 px-4">${sourceBadge}</td>
                    <td class="py-3 px-4 font-mono text-stone-600 truncate max-w-48">${ev.path || '/'}</td>
                    <td class="py-3 px-4 font-mono text-stone-400 text-[11px] truncate max-w-36" title="Anon: ${ev.anonymous_id}">
                        ${ev.user_id ? '<span class="text-emerald-700 font-semibold">' + ev.user_id + '</span>' : ev.anonymous_id.substring(0, 12) + '...'}
                    </td>
                    <td class="py-3 px-4 text-right">
                        <button onclick="inspectEvent(${idx})" class="px-2.5 py-1 text-xs font-semibold bg-stone-100 hover:bg-stone-200 text-stone-800 rounded-lg transition-colors border border-stone-200">
                            <i class="fa-solid fa-code"></i> Inspect
                        </button>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }

    // Modal Inspector
    function inspectEvent(index) {
        const ev = recentEventsData[index];
        if (!ev) return;

        document.getElementById('inspector-title').textContent = `${ev.event_name} (${ev.event_source.toUpperCase()})`;
        document.getElementById('inspector-properties').textContent = JSON.stringify(ev.properties || {}, null, 2);
        document.getElementById('inspector-context').textContent = JSON.stringify(ev.context || {}, null, 2);

        const modal = document.getElementById('inspector-modal');
        modal.classList.remove('opacity-0', 'pointer-events-none');
        modal.firstElementChild.classList.remove('scale-95');
    }

    function closeInspector() {
        const modal = document.getElementById('inspector-modal');
        modal.classList.add('opacity-0', 'pointer-events-none');
        modal.firstElementChild.classList.add('scale-95');
    }

    // Auto-Refresh Controls
    function toggleAutoRefresh() {
        isAutoRefreshing = !isAutoRefreshing;
        const icon = document.getElementById('refresh-icon');
        const label = document.getElementById('refresh-label');

        if (isAutoRefreshing) {
            startAutoRefresh();
            icon.classList.add('text-emerald-600');
            icon.classList.remove('text-stone-400');
            label.textContent = 'Live: On (10s)';
        } else {
            clearInterval(autoRefreshTimer);
            icon.classList.remove('text-emerald-600');
            icon.classList.add('text-stone-400');
            label.textContent = 'Live: Off';
        }
    }

    function startAutoRefresh() {
        clearInterval(autoRefreshTimer);
        autoRefreshTimer = setInterval(() => {
            if (isAutoRefreshing) {
                fetchStats();
            }
        }, 10000);
    }
</script>
@endpush

