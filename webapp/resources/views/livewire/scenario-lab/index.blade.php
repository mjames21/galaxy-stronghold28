{{-- ======================================================================
 File: resources/views/livewire/scenario-lab/index.blade.php
====================================================================== --}}
<div class="p-6 space-y-6 md:grid md:grid-cols-12 md:gap-6 md:space-y-0">

  {{-- ================= Left ================= --}}
  <section class="md:col-span-8 space-y-6">

    {{-- Header --}}
    <header class="space-y-3">
      <h1 class="text-2xl font-semibold">Scenario Model</h1>
      <p class="text-slate-600">
        Test “what-if” changes using your real district election results.
        This page shows the <b>national %</b> and <b>district winners</b> (baseline vs scenario).
      </p>

      <div class="flex flex-wrap gap-2 text-xs">
        <span class="rounded-full border px-3 py-1 bg-white">Baseline = selected election</span>
        <span class="rounded-full border px-3 py-1 bg-white">Scenario = baseline + your points</span>
        <span class="rounded-full border px-3 py-1 bg-white">Winner changed? = YES/NO</span>
      </div>
    </header>

    {{-- Builder --}}
    <div class="rounded-xl border bg-white p-6 shadow-sm text-sm space-y-5">

      <div class="grid md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-slate-700">Scenario name</label>
          <input type="text" wire:model.defer="scenario.name"
                 class="mt-1 w-full border rounded-lg px-3 py-2"
                 placeholder="My Scenario">
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700">Election</label>
          <select wire:model="electionId" class="mt-1 w-full border rounded-lg px-3 py-2 bg-white">
            @foreach($elections as $e)
              <option value="{{ $e['id'] }}">
                {{ $e['name'] }}@if(!empty($e['election_date'])) — {{ $e['election_date'] }}@endif
              </option>
            @endforeach
          </select>
          @error('electionId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
      </div>

      <div class="rounded-lg border bg-slate-50 p-3 text-xs text-slate-700 leading-relaxed">
        <b>What “points” means:</b> Points are <b>percentage points</b>.
        Example: moving from 48% to 50% is <b>+2 points</b>.
        This is not “votes”. It is a simple change to the percentage share.
      </div>

      <div>
        <h2 class="font-semibold">Party points (percentage points)</h2>
        <p class="text-xs text-slate-500 mt-1">
          Example: +2 means party gains 2 points in each selected district.
          After changes, we re-balance totals so everything returns to 100%.
        </p>

        <div class="grid md:grid-cols-4 gap-3 mt-3">
          @foreach($parties as $p)
            <div>
              <label class="block text-sm text-slate-700">{{ $p['short_code'] }}</label>
              <input type="number" step="0.5" wire:model.defer="scenario.swing.{{ $p['short_code'] }}"
                     class="mt-1 w-full border rounded-lg px-3 py-2" value="0">
            </div>
          @endforeach
        </div>
      </div>

      <div>
        <h2 class="font-semibold">Where to apply it (districts)</h2>
        <p class="text-xs text-slate-500 mt-1">
          If you select none, it runs on <b>all districts</b>.
        </p>

        <div class="grid md:grid-cols-3 gap-2 mt-3">
          @foreach($districts as $d)
            <label class="inline-flex items-center gap-2">
              <input type="checkbox" wire:model.defer="scenario.scope.{{ $d['id'] }}">
              <span>{{ $d['name'] }}</span>
            </label>
          @endforeach
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button wire:click="run"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                wire:loading.attr="disabled" wire:target="run">
          <span wire:loading.remove wire:target="run">Run Scenario</span>
          <span wire:loading wire:target="run">Running…</span>
        </button>
      </div>
    </div>

    {{-- Results --}}
    @if($result)

      {{-- ===== National card (ABOVE districts) ===== --}}
      <div class="rounded-xl border bg-white p-5 shadow-sm space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold">National Summary</h2>
            <p class="text-xs text-slate-500">
              National % is computed by summing across the selected districts.
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="rounded-full border px-3 py-1 bg-white">
              Scope: {{ $result['scope_count'] ?? 0 }} district(s)
            </span>
            <span class="rounded-full border px-3 py-1 bg-white">
              Winner changed: {{ $result['national']['baseline_winner'] !== $result['national']['scenario_winner'] ? 'YES' : 'NO' }}
            </span>
          </div>
        </div>

        <div class="grid md:grid-cols-2 gap-3">
          {{-- Baseline --}}
          <div class="rounded-lg border bg-slate-50 p-4">
            <div class="text-xs text-slate-600">Baseline national winner</div>
            <div class="mt-1 text-lg font-semibold">{{ $result['national']['baseline_winner'] ?? '—' }}</div>

            <div class="mt-3 space-y-1 text-sm">
              @foreach(($result['national']['baseline'] ?? []) as $party => $pct)
                <div class="flex items-center justify-between">
                  <span class="font-medium">{{ $party }}</span>
                  <span>{{ (int)$pct }}%</span>
                </div>
              @endforeach
            </div>
          </div>

          {{-- Scenario --}}
          <div class="rounded-lg border bg-white p-4">
            <div class="text-xs text-slate-600">Scenario national winner</div>
            <div class="mt-1 text-lg font-semibold">{{ $result['national']['scenario_winner'] ?? '—' }}</div>

            <div class="mt-3 space-y-1 text-sm">
              @foreach(($result['national']['scenario'] ?? []) as $party => $pct)
                <div class="flex items-center justify-between">
                  <span class="font-medium">{{ $party }}</span>
                  <span>{{ (int)$pct }}%</span>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>

      {{-- ===== Districts card ===== --}}
      <div class="rounded-xl border bg-white p-5 shadow-sm space-y-4">

        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold">District Results</h2>
            <p class="text-xs text-slate-500">
              Shows the district winner and the winning % (baseline vs scenario).
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="rounded-full border px-3 py-1 bg-white">
              Winner changed: {{ $result['changed_count'] ?? 0 }}
            </span>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b bg-gray-50">
                <th class="px-3 py-2 text-left">District</th>

                <th class="px-3 py-2 text-left">Baseline winner</th>
                <th class="px-3 py-2 text-left">Baseline winning %</th>

                <th class="px-3 py-2 text-left">Scenario winner</th>
                <th class="px-3 py-2 text-left">Scenario winning %</th>

                <th class="px-3 py-2 text-left">Winner changed?</th>
              </tr>
            </thead>

            <tbody>
              @foreach(($result['districts'] ?? []) as $row)
                <tr class="border-b last:border-0">
                  <td class="px-3 py-2">{{ $row['district'] }}</td>

                  <td class="px-3 py-2 font-medium">{{ $row['baseline_winner'] }}</td>
                  <td class="px-3 py-2">{{ (int)($row['baseline_winner_pct'] ?? 0) }}%</td>

                  <td class="px-3 py-2 font-medium">{{ $row['scenario_winner'] }}</td>
                  <td class="px-3 py-2">{{ (int)($row['scenario_winner_pct'] ?? 0) }}%</td>

                  <td class="px-3 py-2">
                    @if($row['changed'])
                      <span class="rounded-full border px-2 py-0.5 text-xs bg-red-50 text-red-700">YES</span>
                    @else
                      <span class="rounded-full border px-2 py-0.5 text-xs bg-slate-50 text-slate-700">NO</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <details class="mt-2">
          <summary class="cursor-pointer text-indigo-600 text-sm">Show raw JSON</summary>
          <pre class="mt-2 whitespace-pre-wrap rounded bg-gray-50 p-2 text-xs">{{ json_encode($result, JSON_PRETTY_PRINT) }}</pre>
        </details>
      </div>
    @endif
  </section>

  {{-- ================= Right: Help (scrollable) ================= --}}
  <aside class="md:col-span-4">
    <div class="sticky top-20">
      <div class="rounded-xl border bg-white shadow-sm">
        <div class="px-5 py-3 border-b flex items-center justify-between">
          <h3 class="text-base font-semibold text-slate-800">Help</h3>
          <span class="text-xs text-slate-500">Scroll</span>
        </div>

        <div class="p-5 text-sm space-y-5 overflow-y-auto pr-4" style="max-height: calc(100vh - 7rem);">
          <h3 class="text-base font-semibold text-slate-800">About this model</h3>
          <p class="text-slate-700 leading-relaxed">
            This model answers:
            <b>“If a party gains or loses points, who becomes the winner and what is their winning %?”</b>
          </p>

          <h3 class="text-base font-semibold text-slate-800">What is a “point”?</h3>
          <p class="text-slate-700 leading-relaxed">
            A point is a <b>percentage point</b>.
            Example: moving from 48% to 50% is <b>+2 points</b>.
          </p>

          <h3 class="text-base font-semibold text-slate-800">National Summary</h3>
          <ul class="list-disc pl-5 text-slate-700 space-y-1">
            <li>Shows the national % for <b>Baseline</b> and <b>Scenario</b>.</li>
            <li>National % is calculated by summing across the selected districts.</li>
          </ul>

          <h3 class="text-base font-semibold text-slate-800">District table</h3>
          <ul class="list-disc pl-5 text-slate-700 space-y-1">
            <li><b>Baseline winner</b> = who won that district in the real election.</li>
            <li><b>Scenario winner</b> = who wins after applying your points.</li>
            <li><b>Winner changed?</b> = YES if the winner flipped.</li>
          </ul>

          <h3 class="text-base font-semibold text-slate-800">Important note</h3>
          <p class="text-slate-700 leading-relaxed">
            After applying points, we <b>re-balance</b> totals so all parties still add up to <b>100%</b>.
          </p>

          <p class="text-slate-700 italic border-t pt-4">
            “Use this model to see where small percentage changes can flip districts and change the national picture.”
          </p>
        </div>
      </div>
    </div>
  </aside>

</div>
