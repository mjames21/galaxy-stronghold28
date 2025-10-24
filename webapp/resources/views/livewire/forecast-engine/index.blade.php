{{-- resources/views/livewire/forecast-engine/index.blade.php --}}
<div class="w-full md:grid md:grid-cols-12 gap-6"><!-- ensure 2-col at md+ -->

  {{-- ================= Left: Inputs + Results ================= --}}
  <section class="md:col-span-8 space-y-6">
    {{-- Place this right before the "Inputs" card --}}
<header class="space-y-3">
  <h1 class="text-2xl font-semibold">Forecast Engine</h1>

  {{-- One-line summary --}}
  <p class="text-slate-600">
    Simulate thousands of possible elections and summarize the likely vote shares and turnout.
  </p>

  {{-- Context row: election + live params --}}
  @php
    $currentElection = collect($elections ?? [])->firstWhere('id', $electionId);
  @endphp
  <div class="flex flex-wrap items-center gap-2 text-xs">
    @if($currentElection)
      <span class="rounded-full border px-2.5 py-1 bg-white text-slate-700">
        Election: {{ $currentElection['name'] }}
      </span>
    @endif
    <span class="rounded-full border px-2.5 py-1 bg-white text-slate-700">
      Monte Carlo (N): {{ number_format($simulations ?? 0) }}
    </span>
    <span class="rounded-full border px-2.5 py-1 bg-white text-slate-700">
      Alpha (α): {{ rtrim(rtrim(number_format($alphaSmoothing ?? 0, 2, '.', ''), '0'), '.') }}
    </span>
  </div>

  {{-- Optional: compact explainer --}}
  <details class="group">
    <summary class="cursor-pointer text-indigo-600 text-sm">What does this do?</summary>
    <div class="mt-2 text-sm text-slate-600 space-y-2">
      <p><span class="font-medium">Monte Carlo (N):</span> how many random runs we simulate. More runs → smoother, more stable averages (but slower).</p>
      <p><span class="font-medium">Alpha (α):</span> stability knob for party shares in the Dirichlet model.
         Higher α → shares vary less between runs; lower α → more volatility.</p>
      <ol class="list-decimal pl-5 space-y-1">
        <li>Draw party shares from <span class="font-medium">Dirichlet(α)</span> (they always sum to 100%).</li>
        <li>Draw turnout from a <span class="font-medium">Beta</span> distribution (between 0 and 1).</li>
        <li>Repeat <span class="font-medium">N</span> times; report means and (optionally) intervals.</li>
      </ol>
    </div>
  </details>
</header>


    {{-- Inputs --}}
    <div class="rounded border bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
      <h2 class="text-base font-semibold text-slate-800 dark:text-gray-100">Inputs</h2>

      <div class="grid gap-4 md:grid-cols-4">
        {{-- Election --}}
        <div class="md:col-span-2">
          <label for="election" class="block text-sm font-medium text-slate-600 dark:text-gray-400">Election</label>
          <select id="election" wire:model="electionId"
                  class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
            @foreach($elections as $e)
              <option value="{{ $e['id'] }}">{{ $e['name'] }}</option>
            @endforeach
          </select>
          <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">Choose the dataset (parties, priors, turnout).</p>
          @error('electionId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Monte Carlo N --}}
        <div>
          <label for="sims" class="block text-sm font-medium text-slate-600 dark:text-gray-400">Monte Carlo Runs (N)</label>
          <input id="sims" type="number" min="100" step="100" wire:model.lazy="simulations"
                 class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-800" />
                 <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">How many random simulations to run (more = smoother, slower).</p>
          @error('simulations') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Alpha (tooltip + optional slider) --}}
        <div x-data="{ open:false }" class="relative">
          <div class="flex items-center justify-between">
            <label for="alpha" class="block text-sm font-medium text-slate-600 dark:text-gray-400">
              Alpha (Dirichlet smoothing, α)
            </label>
            <button type="button"
                    class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border text-xs text-slate-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                    @mouseenter="open=true" @mouseleave="open=false" @focus="open=true" @blur="open=false"
                    aria-describedby="alpha-help" aria-label="What is alpha?">?
            </button>
          </div>

          {{-- Slider (optional UX; bound to same model) --}}
          <input type="range" min="0.1" max="5" step="0.1"
                 wire:model.lazy="alphaSmoothing"
                 class="mt-2 w-full accent-indigo-600" />

          {{-- Numeric input --}}
          <input id="alpha" type="number" min="0.1" max="10" step="0.1" wire:model.lazy="alphaSmoothing"
                 class="mt-2 w-28 rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-800" />
          <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">Use 0.5–2.0 for most runs. Default α = 1.0.</p>

          {{-- Tooltip --}}
          <div x-cloak x-show="open"
               class="absolute right-0 z-10 mt-1 w-72 rounded border bg-white p-3 text-xs shadow-lg dark:border-gray-800 dark:bg-gray-900"
               role="tooltip" id="alpha-help">
            <p class="font-medium">Alpha (α) explained</p>
            <ul class="mt-1 list-disc pl-4">
              <li>Controls how <em>stable</em> party shares are in each draw.</li>
              <li>Higher α ⇒ shares vary less (smoother); lower α ⇒ more volatile.</li>
              <li>It feeds the Dirichlet distribution used for party shares.</li>
            </ul>
          </div>
           <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">Controls share stability. Start at <b>α = 1.0</b> (typical range 0.5–2.0).</p>


          @error('alphaSmoothing') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button wire:click="run"
                class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700 disabled:opacity-60"
                wire:loading.attr="disabled" wire:target="run">
          <span wire:loading.remove wire:target="run">Run Simulation</span>
          <span wire:loading wire:target="run">Running…</span>
        </button>
        <button wire:click="resetSim"
                class="rounded bg-gray-200 px-4 py-2 hover:bg-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700">
          Reset
        </button>
      </div>
    </div>

    {{-- Results --}}
    @if($output)
      <div class="rounded border bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <h2 class="text-base font-semibold text-slate-800 dark:text-gray-100">
          Results — {{ number_format($simulations) }} Monte Carlo runs
        </h2>
        <p class="text-sm text-slate-600 dark:text-gray-400">National Mean Estimates</p>

        <div class="mt-4 grid gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
          @foreach($output['national_mean'] as $party => $share)
            <div class="rounded border bg-gray-50 p-3 dark:bg-gray-800">
              <div class="text-sm text-slate-600 dark:text-gray-400">{{ $party }}</div>
              <div class="text-xl font-semibold">{{ number_format($share * 100, 1) }}%</div>
            </div>
          @endforeach
        </div>

        <div class="mt-4 text-sm text-slate-700 dark:text-gray-300">
          <span class="font-medium">Mean Turnout:</span>
          {{ number_format(($output['mean_turnout'] ?? 0) * 100, 1) }}%
        </div>

        {{-- Chart + caption --}}
        <div class="mt-6" wire:ignore>
          <canvas id="party-means-chart" height="140"></canvas>
        </div>
        <p class="mt-2 text-center text-xs text-slate-500 dark:text-gray-400">
          N = {{ number_format($simulations) }} Monte Carlo runs • α = {{ rtrim(rtrim(number_format($alphaSmoothing,2,'.',''), '0'), '.') }}
        </p>

        <script>window.__partyMeans = @json($output['national_mean'] ?? []);</script>

        <details class="mt-4">
          <summary class="cursor-pointer text-indigo-600 text-sm">Show raw JSON</summary>
          <pre class="mt-2 whitespace-pre-wrap rounded bg-gray-50 p-2 text-xs dark:bg-gray-800">{{ json_encode($output, JSON_PRETTY_PRINT) }}</pre>
        </details>
      </div>
    @else
      <div class="rounded border bg-white p-5 text-sm text-slate-600 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
        No results yet. Set inputs and click <b>Run Simulation</b>.
      </div>
    @endif
  </section>

  {{-- ================= Right: Brief Docs (Sticky) ================= --}}
  <aside class="md:col-span-4">
    <div class="sticky top-20 rounded border bg-white p-5 shadow-sm text-sm dark:border-gray-800 dark:bg-gray-900">
      <h3 class="text-base font-semibold text-slate-800 dark:text-gray-100">What you do</h3>
      <ul class="mt-2 list-disc pl-5 text-slate-700 dark:text-gray-300">
        <li><b>Pick an election</b> (dataset of parties, priors, turnout).</li>
        <li><b>Set N</b> = number of <i>Monte Carlo</i> runs (more ⇒ smoother averages).</li>
        <li><b>Set α</b> (Alpha): stability of party shares (↑α ⇒ less swing).</li>
        <li>Click <b>Run Simulation</b>.</li>
      </ul>

      <h3 class="mt-5 text-base font-semibold text-slate-800 dark:text-gray-100">What happens</h3>
      <ol class="mt-2 list-decimal pl-5 text-slate-700 dark:text-gray-300 space-y-1">
        <li>For each run: party shares are drawn from a <b>Dirichlet(α)</b> (sum = 100%).</li>
        <li>Turnout is drawn from a <b>Beta</b> distribution (0–1).</li>
        <li>We repeat this <b>N</b> times and average results.</li>
      </ol>

      <h3 class="mt-5 text-base font-semibold text-slate-800 dark:text-gray-100">What you see</h3>
      <dl class="mt-2 grid grid-cols-[9rem_1fr] gap-y-2">
        <dt class="font-medium">National mean</dt>
        <dd class="text-right text-slate-600 dark:text-gray-400">Avg vote share by party across runs.</dd>
        <dt class="font-medium">Mean turnout</dt>
        <dd class="text-right text-slate-600 dark:text-gray-400">Avg turnout across runs (0–100%).</dd>
        <dt class="font-medium">Chart</dt>
        <dd class="text-right text-slate-600 dark:text-gray-400">Bar chart of party means.</dd>
        <dt class="font-medium">Caption</dt>
        <dd class="text-right text-slate-600 dark:text-gray-400">Shows <b>N</b> and <b>α</b> used.</dd>
      </dl>

      <h3 class="mt-5 text-base font-semibold text-slate-800 dark:text-gray-100">Tips</h3>
      <ul class="mt-2 list-disc pl-5 text-slate-700 dark:text-gray-300">
        <li><b>N</b>: start at 1,000. Raise if results look noisy.</li>
        <li><b>α</b>: 1.0 is neutral. 0.5–2.0 covers most real cases.</li>
      </ul>
    </div>
  </aside>
</div>

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    let partyChart;
    function buildPartyChart() {
      const el = document.getElementById('party-means-chart');
      if (!el || !window.__partyMeans) return;
      const labels = Object.keys(window.__partyMeans);
      const data = Object.values(window.__partyMeans).map(v => Number((v * 100).toFixed(1)));
      if (partyChart) partyChart.destroy(); // why: avoid duplicates after Livewire patch
      partyChart = new Chart(el.getContext('2d'), {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Vote %', data }] },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: { y: { beginAtZero: true, ticks: { callback: v => v + '%' } } },
          plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => `${c.parsed.y}%` } } }
        }
      });
    }
    document.addEventListener('livewire:load', () => {
      buildPartyChart();
      Livewire.hook('message.processed', () => {
        if (document.getElementById('party-means-chart')) buildPartyChart();
      });
    });
  </script>
@endpush
