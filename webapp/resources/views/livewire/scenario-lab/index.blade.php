{{-- resources/views/livewire/scenario-lab/index.blade.php --}}
<div class="p-6 space-y-6 md:grid md:grid-cols-12 md:gap-6">
  {{-- ===== Left: UI ===== --}}
  <section class="md:col-span-8 space-y-6">
    {{-- Header --}}
    <header class="space-y-3">
      <h1 class="text-2xl font-semibold">Scenario Lab</h1>
      <p class="text-slate-600">
        Try a “what-if”, re-run the model, and see how seats move. No heavy stats — just clear changes you can act on.
      </p>
      <div class="flex flex-wrap gap-2 text-xs">
        <span class="rounded-full border px-2.5 py-1 bg-white">Baseline = your starting map</span>
        <span class="rounded-full border px-2.5 py-1 bg-white">Scenario = map after your change</span>
        <span class="rounded-full border px-2.5 py-1 bg-white">Δ = difference (scenario − baseline)</span>
      </div>
    </header>

    {{-- Builder --}}
    <div class="rounded border bg-white p-6 shadow-sm text-sm dark:border-gray-800 dark:bg-gray-900">
      <div class="grid md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-slate-700">Scenario name</label>
          <input type="text" wire:model.defer="scenario.name" class="mt-1 w-full border rounded px-3 py-2" placeholder="My Scenario">
        </div>

        <div x-data="{o:false}">
          <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-slate-700">Turnout change (%)</label>
            <button type="button" class="h-5 w-5 text-xs rounded-full border" @mouseenter="o=true" @mouseleave="o=false" aria-label="Help">?</button>
          </div>
          <input type="number" step="0.5" wire:model.defer="scenario.turnout_delta" class="mt-1 w-full border rounded px-3 py-2" placeholder="+3">
          <div x-cloak x-show="o" class="mt-1 w-64 rounded border bg-white p-2 text-[11px] shadow-lg">
            Adds or subtracts turnout points in selected regions. “+3” means 3 percentage points higher.
          </div>
          <p class="mt-1 text-xs text-slate-500">Tip: test small moves first (±1–3%).</p>
        </div>
      </div>

      <h2 class="font-semibold mt-6">Party swing (%)</h2>
      <div class="grid md:grid-cols-4 gap-3 mt-2">
        @foreach($parties as $p)
          <div x-data="{o:false}">
            <div class="flex items-center justify-between">
              <label class="block text-sm text-slate-700">{{ $p['short_code'] }}</label>
              <button type="button" class="h-5 w-5 text-[10px] rounded-full border" @mouseenter="o=true" @mouseleave="o=false" aria-label="Help">?</button>
            </div>
            <input type="number" step="0.5" wire:model.defer="scenario.swing.{{ $p['short_code'] }}" class="mt-1 w-full border rounded px-3 py-2" value="0">
            <div x-cloak x-show="o" class="mt-1 w-64 rounded border bg-white p-2 text-[11px] shadow-lg">
              Move vote share for {{ $p['short_code'] }} up/down. Keep changes realistic unless testing big shocks.
            </div>
          </div>
        @endforeach
      </div>

      <h2 class="font-semibold mt-6">Scope (Regions)</h2>
      <div class="grid md:grid-cols-4 gap-2 mt-2">
        @foreach($regions as $r)
          <label class="inline-flex items-center space-x-2">
            <input type="checkbox" wire:model.defer="scenario.scope.{{ $r['id'] }}">
            <span>{{ $r['name'] }}</span>
          </label>
        @endforeach
      </div>

      <div class="mt-6 flex items-center gap-2">
        <button wire:click="run" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700" wire:loading.attr="disabled" wire:target="run">
          <span wire:loading.remove wire:target="run">Run Scenario</span>
          <span wire:loading wire:target="run">Running…</span>
        </button>
      </div>
    </div>

    {{-- Results --}}
    @if($result)
      <div class="mt-6 p-4 border rounded bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 class="text-lg font-semibold">Scenario Result: {{ $result['name'] }}</h2>
          <div class="flex flex-wrap items-center gap-2 text-xs">
            @if(!empty($result['n_runs']))
              <span class="rounded-full border px-2.5 py-1 bg-white">N = {{ number_format($result['n_runs']) }} runs</span>
            @endif
            @if(isset($result['scope_count']))
              <span class="rounded-full border px-2.5 py-1 bg-white">Scope = {{ $result['scope_count'] }} region(s)</span>
            @endif
            @if(isset($result['total_delta_seats']))
              <span class="rounded-full border px-2.5 py-1 bg-white">Total Δ Seats = {{ $result['total_delta_seats'] >= 0 ? '+' : '' }}{{ $result['total_delta_seats'] }}</span>
            @endif
            <form method="POST" action="" class="ml-1">
              @csrf
              <input type="hidden" name="payload" value='@json($result)'>
              <button type="submit" class="rounded border px-2.5 py-1 hover:bg-gray-50">Download CSV</button>
            </form>
          </div>
        </div>

        {{-- Party seat changes --}}
        @if(!empty($result['delta_seats']) && is_array($result['delta_seats']))
          <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b bg-gray-50">
                  <th class="px-3 py-2 text-left">Party</th>
                  <th class="px-3 py-2 text-left">Δ Seats</th>
                </tr>
              </thead>
              <tbody>
                @foreach($result['delta_seats'] as $party => $delta)
                  <tr class="border-b last:border-0">
                    <td class="px-3 py-2">{{ $party }}</td>
                    <td class="px-3 py-2">
                      <div class="flex items-center gap-2">
                        <span class="{{ $delta >= 0 ? 'text-green-600' : 'text-red-600' }}">
                          {{ $delta >= 0 ? '+' : '' }}{{ $delta }}
                        </span>
                        @php $abs = min(10, abs((int)$delta)); $w = ($abs/10)*100; @endphp
                        <span class="h-1.5 w-24 rounded bg-gray-100 overflow-hidden">
                          <span class="block h-1.5 {{ $delta >= 0 ? 'bg-green-500' : 'bg-red-500' }}"
                                style="width: {{ $w }}%"></span>
                        </span>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif

        {{-- Quick explainer for the region table --}}
        <div class="mt-6 rounded border bg-gray-50 p-3 text-sm">
          <div class="flex flex-wrap items-center gap-3 text-[11px] text-slate-600">
            <strong class="mr-1 text-slate-700">About this table:</strong>
            <span>“Baseline” = before your changes.</span>
            <span>“Scenario” = after your changes.</span>
            <span>“Δ Seats” = seats gained/lost.</span>
            <span>“Δ Turnout (pts)” = turnout moved up/down in points.</span>
            <span class="inline-flex items-center gap-1">
              <span class="inline-block h-2 w-4 rounded" style="background-color: rgba(239,68,68,.25)"></span> lower turnout
            </span>
            <span class="inline-flex items-center gap-1">
              <span class="inline-block h-2 w-4 rounded" style="background-color: rgba(34,197,94,.25)"></span> higher turnout
            </span>
          </div>
        </div>

        {{-- Region × Seats (+ Δ Turnout) --}}
        @if(!empty($result['regional_seats']) && is_array($result['regional_seats']))
          <div class="mt-3">
            <div class="mt-2 overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead>
                  <tr class="border-b bg-gray-50">
                    <th class="px-3 py-2 text-left">Region</th>
                    <th class="px-3 py-2 text-left">Baseline</th>
                    <th class="px-3 py-2 text-left">Scenario</th>
                    <th class="px-3 py-2 text-left">Δ Seats</th>
                    @if(isset($result['regional_seats'][0]['delta_turnout_pts']))
                      <th class="px-3 py-2 text-left">Δ Turnout (pts)</th>
                    @endif
                  </tr>
                </thead>
                <tbody>
                  @php $sumBase=0;$sumScen=0;$sumDelta=0; @endphp
                  @foreach($result['regional_seats'] as $row)
                    @php
                      $sumBase  += (int)($row['baseline'] ?? 0);
                      $sumScen  += (int)($row['scenario'] ?? 0);
                      $sumDelta += (int)($row['delta'] ?? 0);

                      $dPts   = $row['delta_turnout_pts'] ?? null;
                      $alpha  = isset($dPts) ? min(abs((float)$dPts) / 10, 1) * 0.25 : 0;
                      $bg     = '';
                      if (isset($dPts)) {
                        $bg = $dPts >= 0
                          ? "background-color: rgba(34,197,94,{$alpha});"
                          : "background-color: rgba(239,68,68,{$alpha});";
                      }

                      $absSeats = min(10, abs((int)($row['delta'] ?? 0)));
                      $barW     = ($absSeats/10)*100;
                    @endphp
                    <tr class="border-b last:border-0">
                      <td class="px-3 py-2">{{ $row['region'] }}</td>
                      <td class="px-3 py-2">{{ $row['baseline'] }}</td>
                      <td class="px-3 py-2">{{ $row['scenario'] }}</td>
                      <td class="px-3 py-2">
                        <div class="flex items-center gap-2">
                          <span class="{{ ($row['delta'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ ($row['delta'] ?? 0) >= 0 ? '+' : '' }}{{ $row['delta'] ?? 0 }}
                          </span>
                          <span class="h-1.5 w-20 rounded bg-gray-100 overflow-hidden">
                            <span class="block h-1.5 {{ ($row['delta'] ?? 0) >= 0 ? 'bg-green-500' : 'bg-red-500' }}"
                                  style="width: {{ $barW }}%"></span>
                          </span>
                        </div>
                      </td>
                      @if(isset($row['delta_turnout_pts']))
                        <td class="px-3 py-2" style="{{ $bg }}">
                          <div class="flex items-center gap-2">
                            <span class="{{ ($row['delta_turnout_pts'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                              {{ ($row['delta_turnout_pts'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($row['delta_turnout_pts'] ?? 0, 1) }}
                            </span>
                            @php $absT = min(10, abs((float)($row['delta_turnout_pts'] ?? 0))); $barWT = ($absT/10)*100; @endphp
                            <span class="h-1.5 w-20 rounded bg-gray-100 overflow-hidden">
                              <span class="block h-1.5 {{ ($row['delta_turnout_pts'] ?? 0) >= 0 ? 'bg-green-500' : 'bg-red-500' }}"
                                    style="width: {{ $barWT }}%"></span>
                            </span>
                          </div>
                        </td>
                      @endif
                    </tr>
                  @endforeach

                  {{-- Totals row --}}
                  @php
                    $totBase = $result['regional_totals']['baseline'] ?? $sumBase;
                    $totScen = $result['regional_totals']['scenario'] ?? $sumScen;
                    $totDel  = $result['regional_totals']['delta']    ?? $sumDelta;
                  @endphp
                  <tr class="bg-gray-50 font-medium">
                    <td class="px-3 py-2">Totals</td>
                    <td class="px-3 py-2">{{ $totBase }}</td>
                    <td class="px-3 py-2">{{ $totScen }}</td>
                    <td class="px-3 py-2 {{ $totDel >= 0 ? 'text-green-700' : 'text-red-700' }}">
                      {{ $totDel >= 0 ? '+' : '' }}{{ $totDel }}
                    </td>
                    @if(isset($result['regional_seats'][0]['delta_turnout_pts']))
                      <td class="px-3 py-2 text-slate-500">—</td>
                    @endif
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        @endif

        {{-- Raw JSON (optional) --}}
        <details class="mt-4">
          <summary class="cursor-pointer text-indigo-600 text-sm">Show raw JSON</summary>
          <pre class="mt-2 whitespace-pre-wrap rounded bg-gray-50 p-2 text-xs">{{ json_encode($result, JSON_PRETTY_PRINT) }}</pre>
        </details>

        @if(!empty($result['note']))
          <p class="mt-3 text-xs text-slate-500">{{ $result['note'] }}</p>
        @endif
      </div>
    @endif
  </section>

  {{-- ===== Right: Docs (plain-English) ===== --}}
  <aside class="md:col-span-4">
    <div class="sticky top-20 rounded border bg-white p-5 shadow-sm text-sm dark:border-gray-800 dark:bg-gray-900">
      <h3 class="text-base font-semibold text-slate-800">What’s going on here</h3>
      <p class="mt-2 text-slate-700">
        You make a small change (turnout or swings), the model re-runs, and we show what moved — by party and by region.
        Focus on where seats change and whether turnout shifts explain it.
      </p>

      <h4 class="mt-4 font-semibold text-slate-800">How to read the table</h4>
      <ul class="mt-2 space-y-1 text-slate-700">
        <li><span class="font-medium">Baseline</span>: before your change.</li>
        <li><span class="font-medium">Scenario</span>: after your change.</li>
        <li><span class="font-medium">Δ Seats</span>: seats gained/lost. The tiny bar shows how big the move is.</li>
        <li><span class="font-medium">Δ Turnout (pts)</span>: turnout moved up/down. Green cell = higher, red = lower.</li>
      </ul>

      <h4 class="mt-4 font-semibold text-slate-800">What to do with it</h4>
      <ul class="mt-2 space-y-1 text-slate-700">
        <li>Look for regions with small turnout bumps but seat flips — that’s high leverage.</li>
        <li>If turnout rises but seats don’t, it’s likely safe territory — redirect effort.</li>
        <li>Compare a few scenarios A/B and pick the one with the best “seats per effort”.</li>
      </ul>

      <h4 class="mt-4 font-semibold text-slate-800">Good hygiene</h4>
      <ul class="mt-2 space-y-1 text-slate-700">
        <li>Keep changes realistic (±1–3% first).</li>
        <li>Limit scope to the regions you plan to work.</li>
        <li>Export CSV and attach a short note for leadership.</li>
      </ul>
    </div>
  </aside>
</div>
