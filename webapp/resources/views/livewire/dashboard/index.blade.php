<div>
  @section('title', 'Dashboard — '.config('app.name'))

  @section('page-header')
    <h1 class="text-2xl font-semibold">Stronghold 28 — Leadership Dashboard</h1>
    <p class="text-sm text-gray-600 dark:text-gray-400">
      Unified view of Forecast, Seat Projection, GOTV, and PVT health.
    </p>
  @endsection

  {{-- KPI Cards --}}
  <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-6">
    @forelse($cards as $c)
      <div class="p-5 bg-white dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-800 shadow-sm">
        <div class="text-xs uppercase tracking-wide text-gray-500">{{ $c['title'] }}</div>
        <div class="mt-1 text-2xl font-semibold">{{ $c['value'] }}</div>
      </div>
    @empty
      @for ($i = 0; $i < 4; $i++)
        <div class="p-5 bg-white dark:bg-gray-900 rounded border border-dashed border-gray-200 dark:border-gray-800">
          <div class="text-xs uppercase tracking-wide text-gray-400">Metric</div>
          <div class="mt-1 text-2xl font-semibold text-gray-400">—</div>
        </div>
      @endfor
    @endforelse
  </div>

  {{-- Two-up: National Outlook (chart) + Alerts --}}
  <div class="grid gap-6 lg:grid-cols-2">
    {{-- National Outlook: Vote Distribution --}}
    <div class="p-6 bg-white dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-800 shadow-sm">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">National Outlook</h2>
        <a href="{{ route('forecast') }}" class="text-sm text-indigo-600 hover:underline">Open Forecast</a>
      </div>
      <p class="text-sm text-gray-600 dark:text-gray-400">Vote distribution by party (latest election).</p>

      <div class="mt-4" wire:ignore
           x-data="chartBar({ labels: @js($voteShare['labels']), data: @js($voteShare['data']) })"
           x-init="render($refs.canvas)">
        <canvas x-ref="canvas" class="w-full h-64"></canvas>
      </div>

      <div class="mt-4 grid sm:grid-cols-3 gap-3 text-sm">
        <div class="p-3 rounded bg-gray-50 dark:bg-gray-800/60">
          <div class="text-gray-500">Lead party</div>
          <div class="font-semibold">
            {{ $voteShare['labels'][ array_key_first(array_filter($voteShare['data'], fn($v)=> $v === max($voteShare['data'] ?? [0])) ) ] ?? '—' }}
          </div>
        </div>
        <div class="p-3 rounded bg-gray-50 dark:bg-gray-800/60">
          <div class="text-gray-500">Top share</div>
          <div class="font-semibold">
            {{ isset($voteShare['data']) && count($voteShare['data']) ? max($voteShare['data']) . '%' : '—' }}
          </div>
        </div>
        <div class="p-3 rounded bg-gray-50 dark:bg-gray-800/60">
          <div class="text-gray-500">Parties</div>
          <div class="font-semibold">{{ count($voteShare['labels'] ?? []) }}</div>
        </div>
      </div>
    </div>

    {{-- Alerts --}}
    <div class="p-6 bg-white dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-800 shadow-sm">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Alerts</h2>
        <a href="{{ route('scenarios') }}" class="text-sm text-indigo-600 hover:underline">Create Scenario</a>
      </div>
      @if(!empty($alerts ?? []))
        <ul class="mt-3 space-y-2">
          @foreach($alerts as $a)
            <li class="p-3 rounded border border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-200">
              <div class="text-sm font-medium">{{ $a['title'] }}</div>
              @if(!empty($a['detail']))
                <div class="text-xs opacity-90">{{ $a['detail'] }}</div>
              @endif
            </li>
          @endforeach
        </ul>
      @else
        <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">No alerts yet.</div>
        <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 mt-2">
          <li>Set monitoring thresholds in Forecast Engine.</li>
          <li>Add GOTV targets to receive progress alerts.</li>
        </ul>
      @endif
    </div>
  </div>

  {{-- Turnout Trend (line) --}}
  <div class="mt-6 p-6 bg-white dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-800 shadow-sm">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold">Turnout Trend</h2>
      <a href="{{ route('pvt') }}" class="text-sm text-indigo-600 hover:underline">Open PVT</a>
    </div>
    <p class="text-sm text-gray-600 dark:text-gray-400">Turnout (%) across districts (proxy for temporal updates).</p>

    <div class="mt-4" wire:ignore
         x-data="chartLine({ labels: @js($turnoutTrend['labels']), data: @js($turnoutTrend['data']) })"
         x-init="render($refs.canvas)">
      <canvas x-ref="canvas" class="w-full h-64"></canvas>
    </div>
  </div>

  {{-- Quick Actions --}}
  <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <a href="{{ route('seats') }}" class="p-4 rounded border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/70">
      <div class="text-sm font-semibold">Run Seat Projection</div>
      <div class="text-xs text-gray-500">Monte Carlo → seats + CI</div>
    </a>
    <a href="{{ route('gotv') }}" class="p-4 rounded border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/70">
      <div class="text-sm font-semibold">Open GOTV Lab</div>
      <div class="text-xs text-gray-500">Tune turnout Beta priors</div>
    </a>
    <a href="{{ route('pvt') }}" class="p-4 rounded border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/70">
      <div class="text-sm font-semibold">Verify PVT</div>
      <div class="text-xs text-gray-500">Upload observer tallies</div>
    </a>
    @if (Route::has('reports.seat-projection'))
      <a href="{{ route('reports.seat-projection') }}" class="p-4 rounded border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/70">
        <div class="text-sm font-semibold">Export Seat Report (PDF)</div>
        <div class="text-xs text-gray-500">Share with leadership</div>
      </a>
    @endif
  </div>
</div>

{{-- Simple Alpine chart helpers --}}
@push('scripts')
<script>
  function chartBar({labels=[], data=[]}) {
    let chart;
    const opts = {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Vote share (%)',
          data: data,
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, ticks: { callback: v => v + '%' } } },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: { label: (ctx) => `${ctx.parsed.y}%` }
          }
        }
      }
    };
    return {
      render(canvas) {
        if (chart) chart.destroy();
        chart = new window.Chart(canvas.getContext('2d'), opts);
      }
    };
  }

  function chartLine({labels=[], data=[]}) {
    let chart;
    const opts = {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Turnout (%)',
          data: data,
          borderWidth: 2,
          fill: false,
          tension: 0.25,
          pointRadius: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, ticks: { callback: v => v + '%' } } },
        plugins: { legend: { display: false } }
      }
    };
    return {
      render(canvas) {
        if (chart) chart.destroy();
        chart = new window.Chart(canvas.getContext('2d'), opts);
      }
    };
  }
</script>
@endpush
