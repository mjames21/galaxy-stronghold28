{{-- ===================================================
 File: resources/views/livewire/gotv-lab/index.blade.php
 Update:
 - Do NOT say “page/module”
 - Use “model” wording (like your other screens)
 - Explain numbers in plain English (no math talk)
=================================================== --}}

<div class="w-full md:grid md:grid-cols-12 gap-6">

  {{-- ================= Left ================= --}}
  <section class="md:col-span-8 space-y-6">

    {{-- Header --}}
    <header class="space-y-3">
      <h1 class="text-2xl font-semibold">Turnout Model (GOTV)</h1>
      <p class="text-slate-600">
        This model helps you plan turnout by district. You set two simple inputs and it shows the expected turnout and the change from your baseline.
      </p>
    </header>

    {{-- Inputs --}}
    <div class="rounded border bg-white p-5 shadow-sm space-y-4">
      <h2 class="text-base font-semibold text-slate-800">Inputs</h2>

      <div class="grid gap-4 md:grid-cols-4 items-end">
        <div class="md:col-span-3">
          <label class="block text-sm font-medium text-slate-600">Election</label>
          <select wire:model.live="electionId" class="mt-1 w-full rounded border px-3 py-2 bg-white">
            @foreach($elections as $e)
              <option value="{{ $e['id'] }}">
                {{ $e['name'] }}@if(!empty($e['election_date'])) — {{ $e['election_date'] }}@endif
              </option>
            @endforeach
          </select>
          <p class="mt-1 text-xs text-slate-500">Choose the election you are planning for.</p>
          @error('electionId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
          <button wire:click="exportCsv"
                  class="w-full rounded bg-slate-900 px-4 py-2 text-white text-sm font-semibold hover:bg-slate-800">
            Export CSV
          </button>
        </div>
      </div>
    </div>

    {{-- Status --}}
    @if (session('status'))
      <div class="rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ session('status') }}
      </div>
    @endif

    {{-- Baseline --}}
    <div class="rounded border bg-white p-4 shadow-sm text-sm">
      <div class="flex flex-wrap gap-2">
        <button wire:click="resetBaselinesToCurrent" class="px-3 py-1.5 rounded border hover:bg-gray-50">
          Set baseline to current inputs
        </button>
        <button wire:click="restoreOriginalBaselines" class="px-3 py-1.5 rounded border hover:bg-gray-50">
          Restore baseline
        </button>
        <button wire:click="savePriors" class="px-3 py-1.5 rounded bg-indigo-600 text-white hover:bg-indigo-700">
          Save
        </button>
      </div>
      <p class="mt-2 text-xs text-slate-500">
        Baseline = your reference. “Change” shows how many points you moved from that reference.
      </p>
    </div>

    {{-- Bulk apply --}}
    <div class="rounded border bg-white p-4 shadow-sm text-sm">
      <div class="grid gap-4 md:grid-cols-3 items-end">
        <div>
          <label class="block text-sm font-medium text-slate-600">Mobilisation level (apply to all, optional)</label>
          <input type="number" step="0.1" min="0.5" wire:model.live="alphaAll"
                 class="mt-1 w-full rounded border px-3 py-2 text-sm" placeholder="Try 2 to 6">
          <p class="mt-1 text-xs text-slate-500">
            Bigger number = stronger mobilisation (more work to push turnout up).
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-600">Barrier level (apply to all, optional)</label>
          <input type="number" step="0.1" min="0.5" wire:model.live="betaAll"
                 class="mt-1 w-full rounded border px-3 py-2 text-sm" placeholder="Try 2 to 6">
          <p class="mt-1 text-xs text-slate-500">
            Bigger number = stronger barriers (more things pulling turnout down).
          </p>
        </div>

        <div>
          <button wire:click="applyBulk" class="w-full rounded border px-4 py-2 hover:bg-gray-50 font-semibold">
            Apply to all
          </button>
        </div>
      </div>
    </div>

    {{-- District table --}}
    <div class="overflow-x-auto rounded border bg-white shadow-sm">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="py-2 px-3 text-left">District</th>
            <th class="py-2 px-3 text-left">Code</th>
            <th class="py-2 px-3 text-left">Region</th>

            <th class="py-2 px-3 text-left">
              Mobilisation<br>
              <span class="text-[11px] text-slate-500 font-normal">strength</span>
            </th>

            <th class="py-2 px-3 text-left">
              Barriers<br>
              <span class="text-[11px] text-slate-500 font-normal">difficulty</span>
            </th>

            <th class="py-2 px-3 text-left">Expected turnout</th>
            <th class="py-2 px-3 text-left">Change</th>
          </tr>
        </thead>

        <tbody class="divide-y">
          @foreach($districts as $d)
            @php $id = $d['id']; @endphp
            <tr class="align-top"
                x-data="{
                  a: @entangle('alpha.'.$id).live,
                  b: @entangle('beta.'.$id).live,
                  a0: {{ json_encode($baselineAlpha[$id] ?? 3) }},
                  b0: {{ json_encode($baselineBeta[$id] ?? 2) }},
                  get mean(){ const s=(+this.a||0)+(+this.b||0); return s>0? (+this.a)/s : 0; },
                  get mean0(){ const s0=(+this.a0||0)+(+this.b0||0); return s0>0? (+this.a0)/s0 : 0; },
                  get deltaPts(){ return (this.mean - this.mean0) * 100; }
                }">
              <td class="py-2 px-3 font-medium">{{ $d['name'] }}</td>
              <td class="py-2 px-3 font-mono text-xs">{{ $d['code'] }}</td>
              <td class="py-2 px-3 text-xs text-slate-600">{{ $d['region'] }}</td>

              {{-- Mobilisation --}}
              <td class="py-2 px-3">
                <input type="number" step="0.1" min="0.5"
                       wire:model.live="alpha.{{ $id }}"
                       class="border rounded px-2 py-1 w-28">
                <div class="text-[11px] text-slate-500 mt-1">
                  Higher number = more mobilisation
                </div>
              </td>

              {{-- Barriers --}}
              <td class="py-2 px-3">
                <input type="number" step="0.1" min="0.5"
                       wire:model.live="beta.{{ $id }}"
                       class="border rounded px-2 py-1 w-28">
                <div class="text-[11px] text-slate-500 mt-1">
                  Higher number = more barriers
                </div>
              </td>

              {{-- Expected turnout --}}
              <td class="py-2 px-3">
                <div class="font-semibold" x-text="(mean*100).toFixed(1) + '%'"></div>
                <div class="text-[11px] text-slate-500">
                  Baseline: <span x-text="(mean0*100).toFixed(1) + '%'"></span>
                </div>
              </td>

              {{-- Change --}}
              <td class="py-2 px-3">
                <div :class="deltaPts>0 ? 'text-green-700' : (deltaPts<0 ? 'text-red-700' : 'text-slate-700')"
                     x-text="(deltaPts>=0? '+' : '') + deltaPts.toFixed(1) + ' pts'"></div>
                <div class="text-[11px] text-slate-500">vs baseline</div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- Actions --}}
    <div class="flex flex-wrap items-center gap-2">
      <button wire:click="simulate"
              class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 font-semibold">
        Simulate impact
      </button>
      <button wire:click="savePriors"
              class="px-4 py-2 border rounded hover:bg-gray-50 font-semibold">
        Save
      </button>
    </div>

    {{-- Summary --}}
    @if($summary)
      <div class="rounded border bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-base font-semibold">Summary</h2>
          <span class="text-xs text-slate-500">Based on your current settings</span>
        </div>
        <details class="mt-2">
          <summary class="cursor-pointer text-indigo-600 text-sm">Show details</summary>
          <pre class="mt-2 text-xs whitespace-pre-wrap">{{ json_encode($summary, JSON_PRETTY_PRINT) }}</pre>
        </details>
      </div>
    @endif

  </section>

  {{-- ================= Right: Help (scrollable, SIMPLE, “MODEL” wording) ================= --}}
  <aside class="md:col-span-4">
    <div class="sticky top-20">
      <div class="rounded border bg-white shadow-sm">

        <div class="px-5 py-3 border-b flex items-center justify-between">
          <h3 class="text-base font-semibold text-slate-800">Help</h3>
          <span class="text-xs text-slate-500">Scroll</span>
        </div>

        <div class="p-5 text-sm space-y-5 overflow-y-auto pr-4"
             style="max-height: calc(100vh - 7rem);">

          <h3 class="text-base font-semibold text-slate-800">About the model</h3>
          <p class="text-slate-700 leading-relaxed">
            This turnout model is built on one simple idea:
            turnout goes <b>up</b> when mobilisation is stronger than barriers,
            and turnout goes <b>down</b> when barriers are stronger than mobilisation.
          </p>

          <h3 class="text-base font-semibold text-slate-800">How to understand the numbers</h3>
          <ul class="list-disc pl-5 text-slate-700 space-y-2">
            <li>
              The numbers are just <b>strength levels</b>.
              They do not mean “people” or “money”. They only show <b>more</b> or <b>less</b>.
            </li>
            <li>
              <b>Mobilisation:</b> bigger number = you expect stronger GOTV work.
            </li>
            <li>
              <b>Barriers:</b> bigger number = you expect more difficulties in that district.
            </li>
          </ul>

          <h3 class="text-base font-semibold text-slate-800">Practical examples</h3>
          <ul class="list-disc pl-5 text-slate-700 space-y-2">
            <li>
              If a district is easy to reach and safe, keep <b>barriers</b> lower.
            </li>
            <li>
              If a district needs heavy mobilisation (transport, messaging, volunteers), set <b>mobilisation</b> higher.
            </li>
            <li>
              If you raise mobilisation but barriers stay high, turnout may still not improve much.
            </li>
          </ul>

          <h3 class="text-base font-semibold text-slate-800">Baseline and change</h3>
          <ul class="list-disc pl-5 text-slate-700 space-y-2">
            <li>
              <b>Baseline</b> is your reference point (what you consider normal).
            </li>
            <li>
              <b>Change</b> is shown in <b>percentage points</b> (e.g. +2.0 pts means +2% compared to baseline).
            </li>
          </ul>

          <h3 class="text-base font-semibold text-slate-800">Simple workflow</h3>
          <ol class="list-decimal pl-5 text-slate-700 space-y-2">
            <li>Select the election.</li>
            <li>Adjust mobilisation and barriers.</li>
            <li>Set baseline when you want a new reference.</li>
            <li>Simulate and export.</li>
          </ol>

          <p class="text-slate-700 italic border-t pt-4">
            “Use this model to compare districts and plan resources — not to claim a guaranteed turnout.”
          </p>

        </div>
      </div>
    </div>
  </aside>

</div>
