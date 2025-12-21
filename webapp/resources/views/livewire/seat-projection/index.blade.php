{{-- ===================================================
 File: resources/views/livewire/seat-projection/index.blade.php
 Replicate the Forecast Engine input layout (same spacing + grid style)
 + rounded district values (no decimals)
 + coalitions commented out
=================================================== --}}
@php
  $padCard   = $compact ? 'p-4' : 'p-5';
  $gapPage   = $compact ? 'space-y-4' : 'space-y-6';
  $stickyTop = $compact ? 'top-14' : 'top-20';
  $chartH    = $compact ? 110 : 160;
@endphp

<div class="w-full md:grid md:grid-cols-12 gap-6">

  {{-- ================= Left: Controls + Results ================= --}}
  <section class="md:col-span-8 {{ $gapPage }}">

    {{-- Header --}}
    <header class="space-y-3">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold">Seat Projection</h1>
          <p class="text-slate-600">
            Run many simulations based on past <b>ECSL</b> results, then view expected seats per district and nationally.
          </p>
        </div>

        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" wire:model="compact">
          <span>Compact</span>
        </label>
      </div>
    </header>

    {{-- ============== Inputs card (same style as Forecast Engine) ============== --}}
    <div class="rounded border bg-white {{ $padCard }} shadow-sm space-y-4">
      <h2 class="text-base font-semibold text-slate-800">Inputs</h2>

      {{-- Row 1: Use all elections + election dropdown --}}
      <div class="grid gap-4 md:grid-cols-4 items-end">
        <div class="md:col-span-1">
          <label class="block text-sm font-medium text-slate-600">Data to use</label>
          <label class="mt-2 inline-flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="useAllElections" />
            <span>Use all elections</span>
          </label>
          <p class="mt-2 text-xs text-slate-500 leading-relaxed">
            If unchecked, the projection uses only the selected election year.
          </p>
        </div>

        <div class="md:col-span-3 {{ $useAllElections ? 'opacity-50 pointer-events-none' : '' }}">
          <label class="block text-sm font-medium text-slate-600">Election</label>
          <select wire:model="electionId" class="mt-1 w-full rounded border px-3 py-2">
            @foreach($elections as $e)
              <option value="{{ $e['id'] }}">{{ $e['name'] }}</option>
            @endforeach
          </select>
          <p class="mt-1 text-xs text-slate-500">Used only when “Use all elections” is off.</p>
        </div>
      </div>

      {{-- Row 2: Fade + Reference date + Method + N --}}
      <div class="grid gap-4 md:grid-cols-4">
        <div class="{{ $useAllElections ? '' : 'opacity-50 pointer-events-none' }}">
          <label class="block text-sm font-medium text-slate-600">How fast old results fade (years)</label>
          <input type="number" min="0.1" step="0.1" wire:model.lazy="decayHalfLifeYears"
                 class="mt-1 w-full rounded border px-3 py-2" />
          <p class="mt-1 text-xs text-slate-500">
            Smaller number = recent elections matter more. Larger number = old elections still matter.
          </p>
        </div>

        <div class="{{ $useAllElections ? '' : 'opacity-50 pointer-events-none' }}">
          <label class="block text-sm font-medium text-slate-600">Reference date (today)</label>
          <input type="date" wire:model.lazy="anchorDate"
                 class="mt-1 w-full rounded border px-3 py-2" />
          <p class="mt-1 text-xs text-slate-500">We use this date to decide how old each election is.</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-600">Seat method</label>
          <select wire:model="method" class="mt-1 w-full rounded border px-3 py-2">
            <option value="FPTP">Winner-takes-all (FPTP)</option>
            <option value="PR_DISTRICT_DHONDT">Proportional seats (D’Hondt)</option>
            <option value="PR_DISTRICT_SAINTE_LAGUE">Proportional seats (Sainte-Laguë)</option>
          </select>
          <p class="mt-1 text-xs text-slate-500">
            FPTP = highest votes wins. Proportional = seats shared by vote strength.
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-600">Simulations (N)</label>
          <input type="number" min="200" step="200" wire:model.lazy="simulations"
                 class="mt-1 w-full rounded border px-3 py-2" />
          <p class="mt-1 text-xs text-slate-500">Higher N = smoother results (but slower).</p>
        </div>
      </div>

      {{-- Row 3: alpha + default seats --}}
      <div class="grid gap-4 md:grid-cols-4">
        <div>
          <label class="block text-sm font-medium text-slate-600">Stability (α)</label>
          <input type="number" min="0.1" max="10" step="0.1" wire:model.lazy="alphaSmoothing"
                 class="mt-1 w-full rounded border px-3 py-2" />
          <p class="mt-1 text-xs text-slate-500">
            Higher α = closer to past results. Lower α = more uncertainty.
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-600">Default seats per district</label>
          <input type="number" min="1" wire:model.lazy="defaultSeatsPerDistrict"
                 class="mt-1 w-full rounded border px-3 py-2" />
          <p class="mt-1 text-xs text-slate-500">
            Used if a district has no seat cap in the dataset.
          </p>
        </div>

        <div class="md:col-span-2"></div>
      </div>

      {{-- Coalitions (commented out) --}}
      {{--
      <div class="grid gap-4 md:grid-cols-3">
        <div>
          <label class="block text-sm font-medium text-slate-600">Coalitions</label>
          <input type="text" wire:model.defer="coalitionsInput" class="mt-1 w-full rounded border px-3 py-2" placeholder="SLPP+NGC, APC+C4C">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-600">Majority target</label>
          <input type="number" min="1" wire:model.defer="majorityThreshold" class="mt-1 w-full rounded border px-3 py-2" placeholder="auto">
        </div>
        <div class="flex items-end gap-2">
          <button wire:click="computeCoalitions" class="rounded bg-slate-600 px-3 py-2 text-white hover:bg-slate-700">
            Update coalition chances
          </button>
        </div>
      </div>
      --}}

      {{-- Buttons --}}
      <div class="flex items-center gap-2">
        <button wire:click="run"
                class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700 disabled:opacity-60"
                wire:loading.attr="disabled" wire:target="run">
          <span wire:loading.remove wire:target="run">Run</span>
          <span wire:loading wire:target="run">Running…</span>
        </button>
        <button wire:click="$set('result', null)" class="rounded bg-gray-200 px-4 py-2 hover:bg-gray-300">
          Reset
        </button>
      </div>
    </div>

    {{-- ============== Results ============== --}}
    @if($result)
      <div class="rounded border bg-white {{ $padCard }} shadow-sm space-y-3">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
          <h2 class="text-base font-semibold text-slate-800">National Results</h2>
          <div class="text-xs text-slate-600">
            {{ $result['pooled'] ? 'Using all elections (recent years matter more)' : 'Using one election year only' }}
            • Fade: {{ number_format($result['half_life'],2) }}y
            • Reference: {{ $result['anchor'] }}
            • Total seats: {{ $result['total_seats'] }}
            • N: {{ number_format($result['simulations']) }}
            • α: {{ rtrim(rtrim(number_format($alphaSmoothing,2,'.',''), '0'), '.') }}
          </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-4">
          @foreach($result['summary'] as $party => $s)
            <div class="rounded border bg-gray-50 p-3">
              <div class="text-sm text-slate-600">{{ $party }}</div>
              <div class="text-xl font-semibold">{{ (int) round($s['mean'] ?? 0) }} seats</div>
              <div class="mt-1 text-xs text-slate-600">
                Likely range: {{ (int) ($s['ci95'][0] ?? 0) }}–{{ (int) ($s['ci95'][1] ?? 0) }}
              </div>
            </div>
          @endforeach
        </div>

        <div class="mt-2" wire:ignore>
          <canvas id="seat-means-chart" height="{{ $chartH }}"></canvas>
        </div>
        <script>window.__seatSummary = @json($result['summary'] ?? []);</script>
      </div>

      <div class="rounded border bg-white {{ $padCard }} shadow-sm">
        <h3 class="text-base font-semibold text-slate-800">Per-District Expected Seats</h3>

        <div class="mt-3 overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b">
                <th class="text-left py-2 pr-4">District</th>
                <th class="text-right py-2 pr-4">Seats</th>
                @foreach($result['parties'] as $p)
                  <th class="text-right py-2 pr-4">{{ $p }}</th>
                @endforeach
              </tr>
            </thead>
            <tbody>
              @foreach($result['districts'] as $did => $meta)
                <tr class="border-b hover:bg-gray-50">
                  <td class="py-2 pr-4">{{ $meta['name'] }}</td>
                  <td class="py-2 pr-4 text-right">{{ (int) ($meta['seats'] ?? 0) }}</td>
                  @foreach($result['parties'] as $p)
                    @php $v = $result['district_summary'][$did][$p] ?? 0; @endphp
                    <td class="py-2 pr-4 text-right">{{ (int) round($v) }}</td>
                  @endforeach
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <p class="mt-2 text-[11px] text-slate-500">
          District values are rounded to whole numbers (no decimals).
        </p>
      </div>
    @else
      <div class="rounded border bg-white p-5 text-sm text-slate-600 shadow-sm">
        No results yet. Choose the options, set N and α, then click <b>Run</b>.
      </div>
    @endif

  </section>

  {{-- ================= Right: Help (scrollable) ================= --}}
  <aside class="md:col-span-4">
    <div class="sticky {{ $stickyTop }}">
      <div class="rounded border bg-white shadow-sm">
        <div class="px-5 py-3 border-b flex items-center justify-between">
          <h3 class="text-base font-semibold text-slate-800">Help</h3>
          <span class="text-xs text-slate-500">Scroll</span>
        </div>

        <div class="p-5 text-sm space-y-5 overflow-y-auto pr-4"
             style="max-height: calc(100vh - 7rem);">

          <h3 class="text-base font-semibold text-slate-800">About the model</h3>
          <p class="text-slate-700 leading-relaxed">
            This tool runs many simulations using past <b>ECSL</b> results.
            Each simulation estimates seats per district, then adds them for the national total.
          </p>

          <h3 class="text-base font-semibold text-slate-800">Why “Use all elections” fades old results</h3>
          <p class="text-slate-700 leading-relaxed">
            When we combine many election years, recent elections should matter more than very old ones.
            The fade setting controls how quickly older results lose influence.
          </p>

          <h3 class="text-base font-semibold text-slate-800">Why two proportional methods?</h3>
          <p class="text-slate-700 leading-relaxed">
            Both are standard ways to share seats proportionally.
            <b>D’Hondt</b> slightly favors bigger parties, while <b>Sainte-Laguë</b> is a bit friendlier to smaller parties.
            Choose the one that matches your policy preference.
          </p>

          <h3 class="text-base font-semibold text-slate-800">Quick tips</h3>
          <ul class="list-disc pl-5 text-slate-700 space-y-2">
            <li>If results look too “wild”, increase <b>α</b> or increase <b>N</b>.</li>
            <li>If old elections influence too much, reduce the fade years.</li>
          </ul>

        </div>
      </div>
    </div>
  </aside>

</div>

@push('scripts')
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
        data: { labels, datasets: [{ label: 'Average seats', data }] },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
      });
    }
    document.addEventListener('livewire:load', () => {
      buildSeatChart();
      Livewire.hook('message.processed', () => { if (document.getElementById('seat-means-chart')) buildSeatChart(); });
    });
  </script>
@endpush
