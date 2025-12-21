<div class="p-6 space-y-6 md:grid md:grid-cols-12 md:gap-6 md:space-y-0">

  {{-- ========================= LEFT ========================= --}}
  <section class="md:col-span-8 space-y-6">

    {{-- Header --}}
    <header class="space-y-2">
      <h1 class="text-xl md:text-2xl font-semibold">Voter Registry</h1>
      <p class="text-sm text-slate-600">
        Registered voters per polling station, mapped to an election year.
      </p>
    </header>

    {{-- Status --}}
    @if (session('status'))
      <div class="rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ session('status') }}
      </div>
    @endif

    {{-- Filters + Actions --}}
    <div class="rounded border bg-white p-4 shadow-sm space-y-4">

      <div class="grid md:grid-cols-3 gap-3">
        {{-- Election --}}
        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-slate-600">Election</label>
          <select wire:model.live="electionId" class="mt-1 w-full border rounded-md px-3 py-2 text-sm bg-white">
            @foreach(($elections ?? []) as $e)
              <option value="{{ $e['id'] }}">
                {{ $e['name'] }} @if(!empty($e['election_date'])) — {{ $e['election_date'] }} @endif
              </option>
            @endforeach
          </select>
          @error('electionId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Import --}}
        <div>
          <label class="block text-xs font-medium text-slate-600">Import CSV</label>
          <div class="mt-1 flex items-center gap-2">
            <input type="file" wire:model="csvFile" class="w-full text-sm" />
          </div>
          @error('csvFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

          <div class="mt-2 flex gap-2">
            <button wire:click="importCsv"
                    class="px-3 py-2 rounded bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800"
                    wire:loading.attr="disabled" wire:target="importCsv,csvFile">
              <span wire:loading.remove wire:target="importCsv">Import</span>
              <span wire:loading wire:target="importCsv">Importing…</span>
            </button>

            <button wire:click="exportCsv"
                    class="px-3 py-2 rounded border text-sm font-semibold hover:bg-gray-50">
              Export Stations
            </button>

            <button wire:click="exportDistrictSummaryCsv"
                    class="px-3 py-2 rounded border text-sm font-semibold hover:bg-gray-50">
              Export Districts
            </button>
          </div>

          <p class="mt-2 text-[11px] text-slate-500">
            CSV columns expected: Region, District, Constituency, Ward, CentreID, CentreName, StationID, ID, RegistredVoters.
          </p>
        </div>
      </div>

      {{-- Search + Filters --}}
      <div class="grid md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-slate-600">Search</label>
          <input type="text" wire:model.live="q"
                 class="mt-1 w-full border rounded-md px-3 py-2 text-sm"
                 placeholder="Search centre name, station code, district..." />
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-medium text-slate-600">Region</label>
            <select wire:model.live="region" class="mt-1 w-full border rounded-md px-3 py-2 text-sm bg-white">
              <option value="">All</option>
              @foreach(($regionOptions ?? []) as $r)
                <option value="{{ $r }}">{{ $r }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600">District</label>
            <select wire:model.live="district" class="mt-1 w-full border rounded-md px-3 py-2 text-sm bg-white">
              <option value="">All</option>
              @foreach(($districtOptions ?? []) as $d)
                <option value="{{ $d }}">{{ $d }}</option>
              @endforeach
            </select>
          </div>
        </div>
      </div>
    </div>

    {{-- Totals cards --}}
    <div class="grid md:grid-cols-4 gap-3">
      <div class="rounded border bg-white p-4 shadow-sm">
        <div class="text-xs text-slate-500">Total registered voters</div>
        <div class="text-lg font-semibold">{{ number_format($totals['registered'] ?? 0) }}</div>
      </div>
      <div class="rounded border bg-white p-4 shadow-sm">
        <div class="text-xs text-slate-500">Stations</div>
        <div class="text-lg font-semibold">{{ number_format($totals['stations'] ?? 0) }}</div>
      </div>
      <div class="rounded border bg-white p-4 shadow-sm">
        <div class="text-xs text-slate-500">Centres</div>
        <div class="text-lg font-semibold">{{ number_format($totals['centres'] ?? 0) }}</div>
      </div>
      <div class="rounded border bg-white p-4 shadow-sm">
        <div class="text-xs text-slate-500">Districts</div>
        <div class="text-lg font-semibold">{{ number_format($totals['districts'] ?? 0) }}</div>
      </div>
    </div>

    {{-- District Summary table --}}
    <div class="rounded border bg-white shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b flex items-center justify-between">
        <h2 class="text-sm font-semibold">District Summary</h2>
        <span class="text-xs text-slate-500">Totals grouped by district</span>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-4 py-2 text-left">Region</th>
              <th class="px-4 py-2 text-left">District</th>
              <th class="px-4 py-2 text-right">Registered</th>
              <th class="px-4 py-2 text-right">Stations</th>
              <th class="px-4 py-2 text-right">Centres</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            @foreach(($districtSummary ?? []) as $r)
              <tr>
                <td class="px-4 py-2">{{ $r->region }}</td>
                <td class="px-4 py-2 font-medium">{{ $r->district }}</td>
                <td class="px-4 py-2 text-right">{{ number_format((int)$r->total_registered) }}</td>
                <td class="px-4 py-2 text-right">{{ number_format((int)$r->stations) }}</td>
                <td class="px-4 py-2 text-right">{{ number_format((int)$r->centres) }}</td>
              </tr>
            @endforeach
            @if(empty($districtSummary) || count($districtSummary) === 0)
              <tr>
                <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">No data yet for this election.</td>
              </tr>
            @endif
          </tbody>
        </table>
      </div>
    </div>

    {{-- Stations table --}}
    <div class="rounded border bg-white shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b flex items-center justify-between">
        <h2 class="text-sm font-semibold">Stations</h2>
        <span class="text-xs text-slate-500">Click Edit to update a station</span>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-4 py-2 text-left">District</th>
              <th class="px-4 py-2 text-left">Centre</th>
              <th class="px-4 py-2 text-left">Station</th>
              <th class="px-4 py-2 text-right">Registered</th>
              <th class="px-4 py-2 text-right"></th>
            </tr>
          </thead>
          <tbody class="divide-y">
            @foreach(($rows ?? []) as $r)
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-2">
                  <div class="font-medium">{{ $r->district }}</div>
                  <div class="text-xs text-slate-500">{{ $r->region }}</div>
                </td>

                <td class="px-4 py-2">
                  <div class="font-medium">{{ $r->centre_name }}</div>
                  <div class="text-xs text-slate-500">CentreID: {{ $r->centre_id }}</div>
                </td>

                <td class="px-4 py-2">
                  <div class="font-medium">{{ $r->station_code }}</div>
                  <div class="text-xs text-slate-500">StationID: {{ $r->station_id }}</div>
                </td>

                <td class="px-4 py-2 text-right font-semibold">
                  {{ number_format((int)($r->registered_voters ?? 0)) }}
                </td>

                <td class="px-4 py-2 text-right">
                  <button wire:click="openEdit({{ $r->id }})"
                          class="text-indigo-600 hover:underline text-sm font-medium">
                    Edit
                  </button>
                </td>
              </tr>
            @endforeach

            @if(($rows ?? null) && method_exists($rows, 'count') && $rows->count() === 0)
              <tr>
                <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">
                  No stations found for this filter.
                </td>
              </tr>
            @endif
          </tbody>
        </table>
      </div>

      <div class="px-4 py-3 border-t">
        {{ $rows->links() }}
      </div>
    </div>
  </section>

  {{-- ========================= RIGHT (Edit panel like Results) ========================= --}}
  <aside class="md:col-span-4">
    <div class="sticky top-20 space-y-4">

      <div class="rounded border bg-white p-5 shadow-sm text-sm">
        <h3 class="text-base font-semibold text-slate-800">Edit Station</h3>
        <p class="mt-2 text-slate-600">
          Click <b>Edit</b> on the table to load a station here.
        </p>

        @if($showEdit)
          <div class="mt-4 space-y-3">
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="block text-xs font-medium text-slate-600">Region</label>
                <input type="text" wire:model.defer="form.region" class="mt-1 w-full border rounded px-3 py-2 text-sm">
                @error('form.region') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-600">District</label>
                <input type="text" wire:model.defer="form.district" class="mt-1 w-full border rounded px-3 py-2 text-sm">
                @error('form.district') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
              </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="block text-xs font-medium text-slate-600">Centre ID</label>
                <input type="number" wire:model.defer="form.centre_id" class="mt-1 w-full border rounded px-3 py-2 text-sm">
                @error('form.centre_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-600">Station ID</label>
                <input type="number" wire:model.defer="form.station_id" class="mt-1 w-full border rounded px-3 py-2 text-sm">
                @error('form.station_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
              </div>
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-600">Centre name</label>
              <input type="text" wire:model.defer="form.centre_name" class="mt-1 w-full border rounded px-3 py-2 text-sm">
              @error('form.centre_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-600">Station code</label>
              <input type="text" wire:model.defer="form.station_code" class="mt-1 w-full border rounded px-3 py-2 text-sm">
              @error('form.station_code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="block text-xs font-medium text-slate-600">Constituency</label>
                <input type="number" wire:model.defer="form.constituency" class="mt-1 w-full border rounded px-3 py-2 text-sm">
                @error('form.constituency') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-600">Ward</label>
                <input type="number" wire:model.defer="form.ward" class="mt-1 w-full border rounded px-3 py-2 text-sm">
                @error('form.ward') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
              </div>
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-600">Registered voters</label>
              <input type="number" min="0" wire:model.defer="form.registered_voters" class="mt-1 w-full border rounded px-3 py-2 text-sm">
              @error('form.registered_voters') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-2 pt-2">
              <button wire:click="saveEdit"
                      class="px-4 py-2 bg-slate-900 text-white rounded hover:bg-slate-800 font-semibold"
                      wire:loading.attr="disabled" wire:target="saveEdit">
                <span wire:loading.remove wire:target="saveEdit">Save</span>
                <span wire:loading wire:target="saveEdit">Saving…</span>
              </button>

              <button wire:click="closeEdit"
                      class="px-4 py-2 border rounded hover:bg-gray-50 font-semibold">
                Cancel
              </button>
            </div>
          </div>
        @else
          <div class="mt-4 rounded border bg-slate-50 p-3 text-xs text-slate-600">
            No station selected.
          </div>
        @endif
      </div>

      <div class="rounded border bg-white p-5 shadow-sm text-sm">
        <h3 class="text-base font-semibold text-slate-800">How to use</h3>
        <ol class="mt-2 list-decimal pl-5 space-y-1 text-slate-700">
          <li>Select the election year.</li>
          <li>Import the CSV (or browse existing records).</li>
          <li>Click <b>Edit</b> on a row to update the station.</li>
          <li>Export stations or district totals when needed.</li>
        </ol>
      </div>
    </div>
  </aside>

</div>
