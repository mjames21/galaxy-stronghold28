{{-- resources/views/livewire/seat-projection/index.blade.php --}}
<div class="p-6 space-y-6 md:grid md:grid-cols-12 md:gap-6">
  {{-- ================= Left: Controls + Results ================= --}}
  <section class="md:col-span-8 space-y-6">
 <header class="space-y-3">
  <h1 class="text-2xl font-semibold">Seat Projection</h1>

  {{-- One-line summary --}}
  <p class="text-slate-600">
    Turn vote shares into seats using two families of rules:
    <span class="font-medium">FPTP</span> (winner-takes-all in each district) or
    <span class="font-medium">PR</span> (seats roughly match vote share using divisor methods).
  </p>

  {{-- Quick chips --}}
  <div class="flex flex-wrap gap-2 text-xs">
    <span class="rounded-full border px-2.5 py-1 bg-white text-slate-700">
      FPTP — most votes wins the seat
    </span>
    <span class="rounded-full border px-2.5 py-1 bg-white text-slate-700">
      PR (D’Hondt) — favors larger parties/alliances slightly
    </span>
    <span class="rounded-full border px-2.5 py-1 bg-white text-slate-700">
      PR (Sainte-Laguë) — friendlier to smaller parties
    </span>
  </div>

  {{-- Optional: brief expandable explainer --}}
  <details class="group">
    <summary class="cursor-pointer text-indigo-600 text-sm">
      Learn more about FPTP vs PR
    </summary>
    <div class="mt-2 text-sm text-slate-600 space-y-2">
      <p>
        <span class="font-medium">FPTP:</span> each district elects one winner — the candidate with the
        most votes. This can <em>amplify</em> major parties and under-represent smaller ones even if they
        have substantial national vote share.
      </p>
      <p>
        <span class="font-medium">PR:</span> seats are assigned to parties so totals track vote shares.
        We use divisor formulas:
        <span class="font-medium">D’Hondt</span> (divisors 1,2,3,…) slightly boosts large parties/alliances;
        <span class="font-medium">Sainte-Laguë</span> (1,3,5,…) treats small parties more generously.
        Regional PR applies these rules per region before combining.
      </p>
    </div>
  </details>
</header>

    {{-- Controls --}}
    <div class="rounded border bg-white p-5 shadow-sm space-y-4 dark:border-gray-800 dark:bg-gray-900">
      <div class="grid gap-4 md:grid-cols-6">
        {{-- Election --}}
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">Election</label>
          <select wire:model="electionId" class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
            @foreach($elections as $e)
              <option value="{{ $e['id'] }}">{{ $e['name'] }}</option>
            @endforeach
          </select>
          <p class="mt-1 text-xs text-slate-500">Choose the dataset (parties, districts, priors).</p>
          @error('electionId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Method + tooltip (a) --}}
        <div x-data="{ open:false }" class="relative">
          <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">Method</label>
            <button type="button"
              class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border text-xs text-slate-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
              @mouseenter="open=true" @mouseleave="open=false" @focus="open=true" @blur="open=false"
              aria-describedby="method-help" aria-label="About methods">?</button>
          </div>
          <select wire:model="method" class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
            <option value="FPTP">FPTP</option>
            <option value="PR_DHONDT">PR — D’Hondt</option>
            <option value="PR_SAINTE_LAGUE">PR — Sainte-Laguë</option>
            <option value="PR_REGIONAL_DHONDT">PR — Regional D’Hondt</option>
            <option value="PR_REGIONAL_SAINTE_LAGUE">PR — Regional Sainte-Laguë</option>
          </select>
          <p class="mt-1 text-xs text-slate-500">Rule that assigns seats from votes.</p>

          {{-- Tooltip popover --}}
          <div x-cloak x-show="open"
               class="absolute z-10 right-0 mt-1 w-72 rounded border bg-white p-3 text-xs shadow-lg dark:border-gray-800 dark:bg-gray-900"
               role="tooltip" id="method-help">
            <p class="font-medium mb-1">Seat allocation methods</p>
            <ul class="space-y-1 list-disc pl-4 text-slate-600 dark:text-gray-400">
              <li><b>FPTP:</b> most votes wins the district (boosts big parties).</li>
              <li><b>D’Hondt:</b> divisors 1,2,3… (favors larger/alliances).</li>
              <li><b>Sainte-Laguë:</b> 1,3,5… (kinder to small parties).</li>
              <li><b>Regional PR:</b> apply PR within regions, then combine.</li>
            </ul>
          </div>
        </div>

        {{-- Monte Carlo N --}}
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">Monte Carlo Runs (N)</label>
          <input type="number" min="200" step="200" wire:model.lazy="simulations" class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
          <p class="mt-1 text-xs text-slate-500">How many random simulations to run (more = smoother, slower).</p>
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
              aria-describedby="alpha-help" aria-label="What is alpha?">?</button>
          </div>

          <input type="range" min="0.1" max="5" step="0.1" wire:model.lazy="alphaSmoothing" class="mt-2 w-full accent-indigo-600" />
          <input id="alpha" type="number" min="0.1" max="10" step="0.1" wire:model.lazy="alphaSmoothing"
                 class="mt-2 w-28 rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-800" />
          <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">Controls share stability. Start at <b>α = 1.0</b> (0.5–2.0 typical).</p>

          <div x-cloak x-show="open"
               class="absolute right-0 z-10 mt-1 w-72 rounded border bg-white p-3 text-xs shadow-lg dark:border-gray-800 dark:bg-gray-900"
               role="tooltip" id="alpha-help">
            <p class="font-medium">Alpha (α) explained</p>
            <ul class="mt-1 list-disc pl-4">
              <li>Higher α ⇒ steadier, less variable shares</li>
              <li>Lower α ⇒ more volatility</li>
            </ul>
          </div>

          @error('alphaSmoothing') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- PR seats --}}
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">Total seats (PR only)</label>
          <input type="number" min="1" wire:model.lazy="totalSeats" class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-800" placeholder="auto">
          <p class="mt-1 text-xs text-slate-500">Leave blank to auto-detect.</p>
          @error('totalSeats') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
      </div>

      {{-- Coalitions --}}
      <div class="grid gap-4 md:grid-cols-3">
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">Coalitions</label>
          <input type="text" wire:model.defer="coalitionsInput" class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-800" placeholder="SLPP+NGC, APC+C4C">
          <p class="mt-1 text-xs text-slate-500">Comma-separated. Use + between party codes.</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">Majority threshold</label>
          <input type="number" min="1" wire:model.defer="majorityThreshold" class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-800" placeholder="auto">
          <p class="mt-1 text-xs text-slate-500">Leave blank to use 50%+1 of total seats.</p>
        </div>
        <div class="flex items-end">
          <button wire:click="computeCoalitions" class="px-4 py-2 rounded bg-slate-600 text-white hover:bg-slate-700">
            Update Coalition Probabilities
          </button>
        </div>
      </div>

      <div class="flex gap-2">
        <button wire:click="run" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">Run Seat Projection</button>
        <button wire:click="$set('result', null)" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700">Reset</button>
      </div>
    </div>

    {{-- Results --}}
    @if($result)
      <div class="rounded border bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between gap-3">
          <h2 class="text-lg font-semibold">Results ({{ $result['method'] }})</h2>
          <div class="text-sm text-slate-600">
            Total seats: {{ $result['total_seats'] }} •
            N = {{ number_format($result['simulations']) }} •
            α = {{ rtrim(rtrim(number_format($alphaSmoothing,2,'.',''), '0'), '.') }}
          </div>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-4">
          @foreach($result['summary'] as $party => $s)
            <div class="rounded border p-3 bg-gray-50 dark:bg-gray-800">
              <div class="text-sm text-slate-600">{{ $party }}</div>
              <div class="text-xl font-semibold">{{ number_format($s['mean'],1) }} seats</div>
              <div class="text-xs text-slate-500">95% CI: {{ $s['ci95'][0] }} – {{ $s['ci95'][1] }}</div>
            </div>
          @endforeach
        </div>

        {{-- (b) Seat means bar chart --}}
        <div class="mt-6" wire:ignore>
          <canvas id="seat-means-chart" height="160"></canvas>
        </div>
        <script>
          window.__seatSummary = @json($result['summary'] ?? []);
        </script>

        @if($coalitionOutput)
          <div class="mt-6">
            <h3 class="text-md font-semibold">Majority Probability (≥ {{ $coalitionOutput['threshold'] }} seats)</h3>

            <h4 class="mt-2 text-sm font-medium text-slate-600">Parties</h4>
            <div class="mt-2 grid gap-2 md:grid-cols-4">
              @foreach($coalitionOutput['party'] as $code => $p)
                <div class="rounded border p-2">
                  <div class="text-sm">{{ $code }}</div>
                  <div class="text-lg font-semibold">{{ number_format($p*100,1) }}%</div>
                </div>
              @endforeach
            </div>

            @if(count($coalitionOutput['coalition']))
              <h4 class="mt-4 text-sm font-medium text-slate-600">Coalitions</h4>
              <div class="mt-2 grid gap-2 md:grid-cols-4">
                @foreach($coalitionOutput['coalition'] as $c)
                  <div class="rounded border p-2">
                    <div class="text-sm">{{ $c['label'] }}</div>
                    <div class="text-lg font-semibold">{{ number_format($c['prob']*100,1) }}%</div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        @endif
      </div>
    @endif
  </section>

  {{-- ================= Right: Brief Docs (Sticky) ================= --}}
  <aside class="md:col-span-4">
    <div class="sticky top-20 rounded border bg-white p-5 shadow-sm text-sm dark:border-gray-800 dark:bg-gray-900">
      <h3 class="text-base font-semibold text-slate-800 dark:text-gray-100">How to use</h3>
      <ol class="mt-2 list-decimal pl-5 text-slate-700 dark:text-gray-300 space-y-1">
        <li>Select <b>Election</b> (dataset).</li>
        <li>Choose <b>Method</b> (FPTP or PR variant).</li>
        <li>Set <b>N</b> = Monte Carlo runs.</li>
        <li>Set <b>α</b> = Dirichlet smoothing (↑α ⇒ steadier shares).</li>
        <li>For PR, set <b>Total seats</b> or leave auto.</li>
        <li>Run; optionally define <b>Coalitions</b> and <b>Majority</b> threshold, then update.</li>
      </ol>

      <h3 class="mt-5 text-base font-semibold text-slate-800 dark:text-gray-100">What you get</h3>
      <dl class="mt-2 grid grid-cols-[9rem_1fr] gap-y-2">
        <dt class="font-medium">Seats (mean)</dt>
        <dd class="text-right text-slate-600">Average seats per party.</dd>
        <dt class="font-medium">95% CI</dt>
        <dd class="text-right text-slate-600">Uncertainty range across runs.</dd>
        <dt class="font-medium">Majority %</dt>
        <dd class="text-right text-slate-600">Chance party/coalition ≥ threshold.</dd>
      </dl>

      <h3 class="mt-5 text-base font-semibold text-slate-800 dark:text-gray-100">Method cheat-sheet</h3>
      <ul class="mt-2 space-y-1 text-slate-700 dark:text-gray-300">
        <li><b>FPTP:</b> most votes wins district (big-party bonus).</li>
        <li><b>D’Hondt:</b> divisors 1,2,3… (helps large/alliances).</li>
        <li><b>Sainte-Laguë:</b> divisors 1,3,5… (friendlier to small).</li>
        <li><b>Regional PR:</b> PR applied per region then combined.</li>
      </ul>
    </div>
  </aside>
</div>

@push('scripts')
  {{-- Chart.js for seat chart --}}
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    let seatChart;

    function buildSeatChart() {
      const el = document.getElementById('seat-means-chart');
      const dataObj = window.__seatSummary || {};
      if (!el || !Object.keys(dataObj).length) return;

      const labels = Object.keys(dataObj);
      const data = labels.map(k => Number((dataObj[k]?.mean ?? 0)));

      if (seatChart) seatChart.destroy();
      seatChart = new Chart(el.getContext('2d'), {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Mean seats', data }] },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: { y: { beginAtZero: true } },
          plugins: { legend: { display: false } }
        }
      });
    }

    document.addEventListener('livewire:load', () => {
      buildSeatChart();
      Livewire.hook('message.processed', () => {
        if (document.getElementById('seat-means-chart')) buildSeatChart();
      });
    });
  </script>
@endpush
