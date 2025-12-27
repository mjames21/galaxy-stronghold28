<div class="w-full md:grid md:grid-cols-12 gap-6">

  {{-- Left --}}
  <section class="md:col-span-8 space-y-6">

    <header class="space-y-2">
      <h1 class="text-2xl font-semibold">Forecast Engine</h1>
      <p class="text-sm text-slate-600">
        District-level model. Two-party (SLPP/APC) simulation with turnout derived from population census denominators.
      </p>
    </header>

    {{-- Inputs --}}
    <div class="rounded border bg-white p-5 shadow-sm space-y-4">

      <div class="grid gap-4 md:grid-cols-4">

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-slate-700">Anchor Election</label>
          <select wire:model="electionId" class="mt-1 w-full rounded border px-3 py-2">
            @foreach($elections as $e)
              <option value="{{ $e['id'] }}">{{ $e['name'] }}</option>
            @endforeach
          </select>
          <p class="mt-1 text-xs text-slate-500">
            Pooled mode will use elections of the <b>same type</b> as this election (time-decayed).
          </p>
          @error('electionId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700">Scope</label>
          <select wire:model="scopeMode" class="mt-1 w-full rounded border px-3 py-2">
            <option value="single">Single election</option>
            <option value="pooled">Pooled (same type)</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700">Breakdown</label>
          <select wire:model="breakdown" class="mt-1 w-full rounded border px-3 py-2">
            <option value="district">District</option>
            <option value="national">National</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700">Monte Carlo (N)</label>
          <input type="number" min="200" step="100" wire:model.lazy="simulations"
                 class="mt-1 w-full rounded border px-3 py-2" />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700">Alpha smoothing (α)</label>
          <input type="number" min="0.1" step="0.1" wire:model.lazy="alphaSmoothing"
                 class="mt-1 w-full rounded border px-3 py-2" />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700">Turnout census year</label>
          <select wire:model="turnoutCensusYear" class="mt-1 w-full rounded border px-3 py-2">
            @foreach(($populationYears ?? []) as $y)
              <option value="{{ $y }}">{{ $y }}</option>
            @endforeach
          </select>
          @error('turnoutCensusYear') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700">Turnout denominator</label>
          <select wire:model="turnoutField" class="mt-1 w-full rounded border px-3 py-2">
            <option value="population_18_plus">Population 18+</option>
            <option value="total_population">Total population</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700">District (optional)</label>
          <select wire:model="selectedDistrictId" class="mt-1 w-full rounded border px-3 py-2">
            <option value="">All districts</option>
            @foreach(($districtOptions ?? []) as $d)
              <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
            @endforeach
          </select>
        </div>

      </div>

      @if($scopeMode === 'pooled')
        <div class="grid md:grid-cols-2 gap-4 pt-2">
          <div>
            <label class="block text-sm font-medium text-slate-700">Decay half-life (years)</label>
            <input type="number" min="0.5" step="0.5" wire:model.lazy="decayHalfLifeYears"
                   class="mt-1 w-full rounded border px-3 py-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700">Anchor date</label>
            <input type="date" wire:model.lazy="anchorDate"
                   class="mt-1 w-full rounded border px-3 py-2" />
          </div>
        </div>
      @endif

      <div class="flex items-center gap-2 pt-2">
        <button wire:click="run"
                wire:loading.attr="disabled"
                class="rounded bg-slate-900 px-4 py-2 text-white hover:bg-slate-800 disabled:opacity-50">
          <span wire:loading.remove>Run Simulation</span>
          <span wire:loading>Running…</span>
        </button>

        <button wire:click="resetSim"
                class="rounded bg-gray-100 px-4 py-2 hover:bg-gray-200">
          Reset
        </button>

        @if(($output['breakdown'] ?? null) === 'district')
          <button wire:click="exportDistrictCsv"
                  class="ml-auto rounded border px-4 py-2 hover:bg-gray-50">
            Export CSV
          </button>
        @endif
      </div>
    </div>

    {{-- Results --}}
    @if($output)
      @if(($output['breakdown'] ?? '') === 'national')
        <div class="rounded border bg-white p-5 shadow-sm">
          <h2 class="text-base font-semibold">National Summary</h2>
          <div class="mt-4 grid grid-cols-2 gap-3">
            <div class="rounded border bg-gray-50 p-3">
              <div class="text-sm text-slate-600">SLPP</div>
              <div class="text-xl font-semibold">{{ number_format(($output['national_mean']['SLPP'] ?? 0) * 100, 1) }}%</div>
            </div>
            <div class="rounded border bg-gray-50 p-3">
              <div class="text-sm text-slate-600">APC</div>
              <div class="text-xl font-semibold">{{ number_format(($output['national_mean']['APC'] ?? 0) * 100, 1) }}%</div>
            </div>
          </div>

          <div class="mt-3 text-sm text-slate-700">
            <span class="font-medium">Mean turnout:</span>
            {{ number_format(($output['mean_turnout'] ?? 0) * 100, 1) }}%
          </div>

          <p class="mt-3 text-xs text-slate-500">
            Turnout uses census {{ $output['turnout_census_year'] ?? '' }} and field {{ $output['turnout_field'] ?? '' }}.
          </p>
        </div>
      @else
        <div class="rounded border bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold">District Forecast</h2>
            <p class="text-xs text-slate-500">
              Census {{ $output['turnout_census_year'] ?? '' }} · {{ $output['turnout_field'] ?? '' }}
            </p>
          </div>

          <div class="overflow-x-auto mt-4">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b text-slate-600">
                  <th class="py-2 text-left cursor-pointer" wire:click="setSort('name')">District</th>
                  <th class="py-2 text-left cursor-pointer" wire:click="setSort('slpp_mean')">SLPP</th>
                  <th class="py-2 text-left cursor-pointer" wire:click="setSort('apc_mean')">APC</th>
                  <th class="py-2 text-left cursor-pointer" wire:click="setSort('turnout_mean')">Turnout</th>
                  <th class="py-2 text-left cursor-pointer" wire:click="setSort('others')">Others</th>
                </tr>
              </thead>
              <tbody>
                @foreach(($output['districts'] ?? []) as $r)
                  <tr class="border-b">
                    <td class="py-2 pr-4">{{ $r['name'] }}</td>
                    <td class="py-2 pr-4">{{ number_format(($r['slpp_mean'] ?? 0) * 100, 1) }}%</td>
                    <td class="py-2 pr-4">{{ number_format(($r['apc_mean'] ?? 0) * 100, 1) }}%</td>
                    <td class="py-2 pr-4">{{ number_format(($r['turnout_mean'] ?? 0) * 100, 1) }}%</td>
                    <td class="py-2 pr-4">{{ number_format(($r['others'] ?? 0) * 100, 1) }}%</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <p class="mt-3 text-xs text-slate-500">
            Note: Shares are modeled for SLPP/APC only (normalized to 100%). “Others” is context from raw totals.
          </p>
        </div>
      @endif
    @else
      <div class="rounded border bg-white p-5 text-sm text-slate-600 shadow-sm">
        No results yet. Pick settings and click <b>Run Simulation</b>.
      </div>
    @endif

  </section>

  {{-- Right: Explanation --}}
 {{-- Right: About / Model --}}
<aside class="md:col-span-4">
  <div class="sticky top-20 rounded border bg-white p-5 shadow-sm text-sm space-y-4">

    <div>
      <h3 class="text-base font-semibold text-slate-800">About the Forecast Model</h3>
      <p class="mt-2 text-slate-700">
        This module is a <b>planning forecast</b>. It simulates the next election outcome using historical results
        to estimate likely <b>vote share percentages</b> for SLPP and APC (district or national).
      </p>
    </div>

    <div class="rounded border bg-slate-50 p-3">
      <div class="font-semibold text-slate-800">What the model uses</div>
      <ul class="mt-2 text-xs text-slate-700 list-disc pl-5 space-y-1">
        <li><b>District results</b> from your Results table (votes by party per district).</li>
        <li><b>Population denominators</b> from DistrictPopulation (to estimate turnout).</li>
        <li>Optional: <b>Pooled mode</b> blends elections of the same type with time decay (newer elections matter more).</li>
      </ul>
    </div>

    <div class="rounded border bg-slate-50 p-3">
      <div class="font-semibold text-slate-800">What the model produces</div>
      <ul class="mt-2 text-xs text-slate-700 list-disc pl-5 space-y-1">
        <li><b>SLPP %</b> and <b>APC %</b> (two-party share: SLPP + APC = 100%).</li>
        <li><b>Uncertainty bands</b>: 50% range and 95% range (how wide the simulation spread is).</li>
        <li><b>Turnout estimate</b> (district votes divided by selected population denominator).</li>
        <li><b>Others %</b> as context from raw totals (not simulated as a party).</li>
      </ul>
    </div>

    <div class="rounded border bg-slate-50 p-3">
      <div class="font-semibold text-slate-800">How to interpret the ranges</div>
      <p class="mt-2 text-xs text-slate-700">
        Each run produces a slightly different outcome. We summarize the runs:
      </p>
      <ul class="mt-2 text-xs text-slate-700 list-disc pl-5 space-y-1">
        <li><b>Mean</b>: average of all runs (your “best estimate”).</li>
        <li><b>50% range</b>: the middle band (typical outcomes).</li>
        <li><b>95% range</b>: wider band (almost all outcomes).</li>
      </ul>
      <p class="mt-2 text-xs text-slate-600">
        Wider ranges = more uncertainty/volatility in that district.
      </p>
    </div>

    <div class="rounded border bg-slate-50 p-3">
      <div class="font-semibold text-slate-800">Why “Simulations (N)” matters</div>
      <p class="mt-2 text-xs text-slate-700">
        N is how many times we run the forecast. Higher N makes results steadier and ranges more reliable, but takes longer.
      </p>
    </div>

    <div class="rounded border border-amber-200 bg-amber-50 p-3">
      <div class="font-semibold text-amber-900 text-xs">Limits (important)</div>
      <ul class="mt-2 text-xs text-amber-900 list-disc pl-5 space-y-1">
        <li>This is not “guaranteed results” — it’s a decision tool.</li>
        <li>Turnout is estimated from census denominators, not from polling-station registered voters (yet).</li>
        <li>Only SLPP/APC are simulated (two-party focus). Others is displayed as context.</li>
      </ul>
    </div>

    <div class="rounded border bg-slate-50 p-3">
      <div class="font-semibold text-slate-800">Best way to use it (campaign-style)</div>
      <ul class="mt-2 text-xs text-slate-700 list-disc pl-5 space-y-1">
        <li>Use <b>Pooled</b> to stabilize results when you want “trend-informed” forecasts.</li>
        <li>Use <b>Single</b> to mirror one election baseline closely.</li>
        <li>Look for <b>districts with tight 95% ranges</b> (more predictable) vs wide ranges (swing districts).</li>
      </ul>
    </div>

  </div>
</aside>


</div>
