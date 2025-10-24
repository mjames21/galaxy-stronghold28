{{-- resources/views/livewire/gotv-lab/index.blade.php --}}
<div class="p-6 space-y-6 md:grid md:grid-cols-12 md:gap-6">
  {{-- ===== Left: UI ===== --}}
  <section class="md:col-span-8 space-y-6">
    {{-- Header --}}
    <header class="space-y-3">
      <h1 class="text-2xl font-semibold">GOTV Lab</h1>
      <p class="text-slate-600">
        Tune regional turnout priors and see the impact. <span class="font-medium">Alpha (α)</span> raises engagement; <span class="font-medium">Beta (β)</span> captures barriers.
      </p>
      <div class="flex flex-wrap gap-2 text-xs">
        <span class="rounded-full border px-2.5 py-1 bg-white">Mean ≈ α / (α + β)</span>
        <span class="rounded-full border px-2.5 py-1 bg-white">Baseline = comparison reference</span>
        <span class="rounded-full border px-2.5 py-1 bg-white">Δ pts = (mean − baseline) × 100</span>
      </div>
    </header>

    {{-- Baseline toolbar --}}
    <div class="rounded border bg-white p-4 shadow-sm text-sm dark:border-gray-800 dark:bg-gray-900">
      <div class="flex flex-wrap gap-2">
        <button wire:click="resetBaselinesToCurrent" class="px-3 py-1.5 rounded border hover:bg-gray-50">
          Set baseline = current α/β
        </button>
        <button wire:click="restoreOriginalBaselines" class="px-3 py-1.5 rounded border hover:bg-gray-50">
          Restore original baseline
        </button>
      </div>
      <p class="mt-2 text-xs text-slate-500">
        Baseline is your fixed reference. Δ compares current mean against it.
      </p>
    </div>

    {{-- Editable priors table with inline help + live mean + Δ vs baseline + relative Δ --}}
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b">
            <th class="py-2 text-left">Region</th>

            <th class="py-2 text-left" x-data="{o:false}">
              <div class="flex items-center gap-2">
                Alpha (α)
                <button type="button" class="h-5 w-5 text-xs rounded-full border"
                        @mouseenter="o=true" @mouseleave="o=false" aria-label="Alpha help">?</button>
              </div>
              <div x-cloak x-show="o" class="mt-1 w-64 rounded border bg-white p-2 text-xs shadow-lg">
                Engagement strength. ↑ α ⇒ ↑ mean turnout.
              </div>
            </th>

            <th class="py-2 text-left" x-data="{o:false}">
              <div class="flex items-center gap-2">
                Beta (β)
                <button type="button" class="h-5 w-5 text-xs rounded-full border"
                        @mouseenter="o=true" @mouseleave="o=false" aria-label="Beta help">?</button>
              </div>
              <div x-cloak x-show="o" class="mt-1 w-64 rounded border bg-white p-2 text-xs shadow-lg">
                Barriers/costs. ↑ β ⇒ ↓ mean turnout.
              </div>
            </th>

            <th class="py-2 text-left" x-data="{o:false}">
              <div class="flex items-center gap-2">
                Mean
                <button type="button" class="h-5 w-5 text-xs rounded-full border"
                        @mouseenter="o=true" @mouseleave="o=false" aria-label="Mean help">?</button>
              </div>
              <div x-cloak x-show="o" class="mt-1 w-64 rounded border bg-white p-2 text-xs shadow-lg">
                Expected turnout = α / (α + β). Shown as %.
              </div>
            </th>

            <th class="py-2 text-left" x-data="{o:false}">
              <div class="flex items-center gap-2">
                Δ mean (pts)
                <button type="button" class="h-5 w-5 text-xs rounded-full border"
                        @mouseenter="o=true" @mouseleave="o=false" aria-label="Delta help">?</button>
              </div>
              <div x-cloak x-show="o" class="mt-1 w-64 rounded border bg-white p-2 text-xs shadow-lg">
                Absolute change in percentage points: (mean − baseline) × 100.
              </div>
            </th>
          </tr>
        </thead>

        <tbody>
          @foreach($regions as $r)
            @php $id = $r['id']; @endphp
            <tr class="border-b align-top"
                x-data="{
                  a: @entangle('alpha.'.$id).live,
                  b: @entangle('beta.'.$id).live,
                  a0: {{ json_encode($baselineAlpha[$id] ?? 3) }},
                  b0: {{ json_encode($baselineBeta[$id] ?? 2) }},
                  get mean(){ const s=(+this.a||0)+(+this.b||0); return s>0? (+this.a)/s : 0; },
                  get mean0(){ const s0=(+this.a0||0)+(+this.b0||0); return s0>0? (+this.a0)/s0 : 0; },
                  get deltaPts(){ return (this.mean - this.mean0) * 100; },
                  get deltaRel(){ return this.mean0>0 ? ((this.mean / this.mean0) - 1) * 100 : 0; },
                  get barWidth(){ const v=Math.min(20, Math.abs(this.deltaPts)); return (v/20)*100; } // cap ±20 pts
                }">

              <td class="py-2 pr-4">{{ $r['name'] }}</td>

              <td class="py-2 pr-4">
                <input type="number" step="0.1" min="0.5"
                       x-model.number="a"
                       wire:model.live="alpha.{{ $id }}"
                       class="border rounded px-2 py-1 w-28" />
                <p class="mt-1 text-xs text-slate-500">Higher α ⇒ higher mean.</p>
              </td>

              <td class="py-2 pr-4">
                <input type="number" step="0.1" min="0.5"
                       x-model.number="b"
                       wire:model.live="beta.{{ $id }}"
                       class="border rounded px-2 py-1 w-28" />
                <p class="mt-1 text-xs text-slate-500">Higher β ⇒ lower mean.</p>
              </td>

              <td class="py-2 pr-4">
                <div class="flex items-center gap-3">
                  <div>
                    <div class="font-medium" x-text="(mean*100).toFixed(1) + '%'"></div>
                    <div class="text-[11px] text-slate-500">Baseline: <span x-text="(mean0*100).toFixed(1) + '%'"></span></div>
                  </div>
                  <div class="h-2 w-40 rounded bg-gray-100 overflow-hidden">
                    <div class="h-2" :style="`width:${(mean*100).toFixed(0)}%`"></div>
                  </div>
                </div>
              </td>

              <td class="py-2 pr-4">
                <div class="flex items-center gap-3">
                  <div>
                    <div :class="deltaPts>0 ? 'text-green-600' : (deltaPts<0 ? 'text-red-600' : 'text-slate-600')"
                         x-text="(deltaPts>=0? '+' : '') + deltaPts.toFixed(1) + ' pts'"></div>
                    <div class="text-[11px] text-slate-500"
                         x-text="(deltaRel>=0? '+' : '') + deltaRel.toFixed(1) + '%'"></div>
                  </div>
                  <div class="h-2 w-32 rounded bg-gray-100 overflow-hidden">
                    <div class="h-2"
                         :class="deltaPts>=0 ? 'bg-green-500' : 'bg-red-500'"
                         :style="`width:${barWidth}%`"></div>
                  </div>
                </div>
                <p class="mt-1 text-xs text-slate-500">Rel Δ = (mean / baseline − 1) × 100.</p>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <button wire:click="simulate" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
      Simulate GOTV Impact
    </button>

    @if($summary)
      <div class="mt-6 p-4 border rounded bg-white">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold">Summary (demo)</h2>
          <span class="text-xs text-slate-500">Updated with current α, β</span>
        </div>
        <pre class="text-xs whitespace-pre-wrap">{{ json_encode($summary, JSON_PRETTY_PRINT) }}</pre>
      </div>
    @endif
  </section>

  {{-- ===== Right: Docs ===== --}}
  <aside class="md:col-span-4">
    <div class="sticky top-20 rounded border bg-white p-5 shadow-sm text-sm dark:border-gray-800 dark:bg-gray-900">
      <h3 class="text-base font-semibold text-slate-800 dark:text-gray-100">GOTV Lab — What it does</h3>
      <p class="mt-2 text-slate-700 dark:text-gray-300">
        Model regional turnout with <span class="font-medium">Beta(α, β)</span>. Edit α (engagement) and β (barriers), then simulate the expected lift.
      </p>

      <h4 class="mt-4 font-semibold text-slate-800 dark:text-gray-100">Variables</h4>
      <ul class="mt-2 space-y-1 text-slate-700 dark:text-gray-300">
        <li><span class="font-medium">α (Alpha)</span> — engagement strength. ↑ α ⇒ ↑ mean.</li>
        <li><span class="font-medium">β (Beta)</span> — barriers strength. ↑ β ⇒ ↓ mean.</li>
        <li><span class="font-medium">mean_turnout</span> — expected turnout = α / (α + β).</li>
        <li><span class="font-medium">baseline_mean</span> — fixed reference used for comparison.</li>
        <li><span class="font-medium">delta_pts</span> — (mean − baseline) × 100 (percentage points).</li>
        <li><span class="font-medium">relative Δ %</span> — (mean / baseline − 1) × 100.</li>
      </ul>

      <h4 class="mt-4 font-semibold text-slate-800 dark:text-gray-100">Workflow</h4>
      <ol class="mt-2 list-decimal pl-5 space-y-1 text-slate-700 dark:text-gray-300">
        <li>Start with baseline α, β (captured at load or when you set baseline).</li>
        <li>Tweak α/β per region; watch mean, Δ pts, and relative Δ update live.</li>
        <li>Click <span class="font-medium">Simulate GOTV Impact</span> to refresh results.</li>
        <li>Re-base anytime using “Set baseline = current α/β”.</li>
      </ol>

      <h4 class="mt-4 font-semibold text-slate-800 dark:text-gray-100">Tips</h4>
      <ul class="mt-2 space-y-1 text-slate-700 dark:text-gray-300">
        <li>Keep α, β ≥ 0.5 for stability.</li>
        <li>Use small steps (+0.1) to test realistic lifts.</li>
        <li>Δ pts shows magnitude; relative Δ shows proportional change.</li>
      </ul>
    </div>
  </aside>
</div>
