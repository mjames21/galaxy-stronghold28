<div class="px-6 py-6 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">District Populations</h1>
            <p class="text-sm text-gray-600">
                Census-based population baselines used for turnout and eligible-voter modeling.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <label class="text-sm text-gray-700">
                Filter by year:
                <select
                    wire:model.live="filterYear"
                    class="ml-2 border rounded px-3 py-1.5 text-sm"
                >
                    <option value="all">All years</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </label>

            <button
                type="button"
                wire:click="createNew"
                class="hidden md:inline-flex items-center px-3 py-1.5 rounded bg-gray-900 text-white text-sm hover:bg-black"
            >
                New Record
            </button>
        </div>
    </div>

    {{-- Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Table --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border">
                <div class="px-4 py-3 border-b flex items-center justify-between text-sm text-gray-600">
                    <span>Population Records</span>
                    <span class="text-xs text-gray-400">
                        {{ $rows->count() }} rows
                        @if($filterYear !== 'all')
                            • Year {{ $filterYear }}
                        @else
                            @if(count($years))
                                • Years:
                                {{ implode(', ', $years) }}
                            @endif
                        @endif
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-2 text-left">District</th>
                            <th class="px-4 py-2 text-left">Year</th>
                            <th class="px-4 py-2 text-right">Total Pop</th>
                            <th class="px-4 py-2 text-right">18+ (opt)</th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $row)
                            <tr>
                                <td class="px-4 py-2">
                                    {{ $row->district?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-2">
                                    {{ $row->census_year }}
                                </td>
                                <td class="px-4 py-2 text-right font-medium">
                                    {{ number_format($row->total_population) }}
                                </td>
                                <td class="px-4 py-2 text-right text-gray-500">
                                    @if(!is_null($row->population_18_plus))
                                        {{ number_format($row->population_18_plus) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <button
                                        wire:click="edit({{ $row->id }})"
                                        class="text-indigo-600 hover:text-indigo-800 text-xs font-medium"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500 text-sm">
                                    No population records found. Add one using the form on the right
                                    or import from CSV.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right column: form + import --}}
        <div class="space-y-6">
            {{-- Form --}}
            <div class="bg-white rounded-lg shadow-sm border p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">
                            {{ $editingId ? 'Edit Population Record' : 'New Population Record' }}
                        </h2>
                        <p class="text-xs text-gray-500">
                            District-level census population. If 18+ is unknown, leave blank.
                        </p>
                    </div>
                </div>

                {{-- Flash --}}
                @if (session('status'))
                    <div class="text-xs text-green-700 bg-green-50 border border-green-100 rounded px-2 py-1.5">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">District</label>
                        <select wire:model="district_id"
                                class="mt-1 w-full border rounded px-3 py-2 text-sm">
                            <option value="">Select district…</option>
                            @foreach($districtOptions as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                        @error('district_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex gap-3">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-700">Census Year</label>
                            <input type="number" wire:model="census_year"
                                   class="mt-1 w-full border rounded px-3 py-2 text-sm">
                            @error('census_year')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-700">Total Population</label>
                            <input type="number" wire:model="total_population"
                                   class="mt-1 w-full border rounded px-3 py-2 text-sm">
                            @error('total_population')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700">
                            Population 18+ <span class="text-[10px] text-gray-500">(optional)</span>
                        </label>
                        <input type="number" wire:model="population_18_plus"
                               class="mt-1 w-full border rounded px-3 py-2 text-sm">
                        @error('population_18_plus')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        <p class="mt-1 text-[11px] text-gray-500">
                            If unknown, leave blank. StrongHold 28 can estimate eligible voters from age pyramid.
                        </p>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button"
                                wire:click="save"
                                class="inline-flex items-center px-4 py-2 rounded bg-gray-900 text-white text-sm font-semibold hover:bg-black">
                            Save Population
                        </button>

                        @if($editingId)
                            <button type="button"
                                    wire:click="createNew"
                                    class="text-xs text-gray-500 hover:text-gray-700">
                                Cancel edit
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Import --}}
            <div class="bg-white rounded-lg shadow-sm border p-4 space-y-3">
                <div>
                    <h2 class="text-sm font-semibold text-gray-800">Import from CSV</h2>
                    <p class="text-xs text-gray-500">
                        Columns:
                        <span class="font-mono text-[11px]">
                            district, census_year, total_population, population_18_plus
                        </span>.
                        District name must match the Districts module.
                    </p>
                </div>

                <div class="space-y-2">
                    <input type="file" wire:model="importFile" class="text-xs">
                    @error('importFile')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror>

                    <button type="button"
                            wire:click="import"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center px-3 py-1.5 rounded bg-gray-900 text-white text-xs font-semibold hover:bg-black disabled:opacity-60">
                        <span wire:loading.remove wire:target="import">Run Import</span>
                        <span wire:loading wire:target="import">Importing…</span>
                    </button>
                </div>

                @if($importMessage)
                    <div class="text-xs text-green-700 bg-green-50 border border-green-100 rounded px-2 py-1.5">
                        {{ $importMessage }}
                    </div>
                @endif

                @if($importErrors)
                    <div class="mt-2 max-h-40 overflow-auto text-xs text-red-700 bg-red-50 border border-red-100 rounded px-2 py-1.5 space-y-1">
                        @foreach($importErrors as $err)
                            <div>• {{ $err }}</div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Small docs hint at bottom --}}
    <div class="pt-2 text-[11px] text-gray-500">
        Tip: keep one record per district per census year (e.g. 2004, 2015, 2021). StrongHold 28
        uses these as baselines for turnout and GOTV capacity modeling.
    </div>
</div>
