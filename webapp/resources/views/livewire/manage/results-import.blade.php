<div class="p-6 space-y-8">
    {{-- Page header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Election Results Import</h1>
            <p class="text-sm text-slate-600">
                Upload ECSL results or enter district totals and map them into
                Stronghold 28’s <span class="font-mono">results</span> table for modeling.
            </p>
        </div>
    </div>

    {{-- How this works --}}
    <div class="bg-white border rounded-lg p-4 text-sm text-slate-700">
        <h2 class="text-sm font-semibold mb-2">How this import works</h2>
        <ol class="list-decimal list-inside space-y-1 text-slate-600">
            <li>Select the election this file belongs to (e.g. <strong>2007 Presidential – First Round</strong>).</li>
            <li>Upload a CSV with <strong>district-level totals</strong>:
                <span class="font-mono">
                    district, turnout_pct, invalid_votes, valid_votes, total_votes, APC, SLPP, …
                </span>
            </li>
            <li>District names must match the Districts module. Party columns must match
                <span class="font-mono">parties.short_code</span> (e.g. SLPP, APC, NGC).
            </li>
            <li>Stronghold creates a synthetic polling station per district (e.g. <span class="font-mono">DIST-BO-SYNTH</span>)
                so turnout and seat probabilities can still be modeled.
            </li>
            <li>You can also skip CSV and use the panel on the right for quick manual district entry.</li>
        </ol>
        <p class="mt-2 text-xs text-slate-500">
            Tip: Older NEC files that only publish district totals (like your 2007 sheet) work well
            with this format. Stronghold will still model turnout and seat probabilities correctly.
        </p>
    </div>

    {{-- Main content: left = import + glossary, right = manual entry --}}
    <div class="grid lg:grid-cols-3 gap-6">

        {{-- LEFT COLUMN: Import + glossary --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Import card --}}
            <div class="bg-white border rounded-lg shadow-sm p-4 space-y-4 text-sm">
                <div class="grid md:grid-cols-3 gap-4">
                    {{-- Election select --}}
                    <div class="md:col-span-1">
                        <label class="block text-xs font-medium text-slate-600">Election</label>
                        <select wire:model="election_id"
                                class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
                            <option value="">Select election…</option>
                            @foreach($elections as $e)
                                <option value="{{ $e['id'] }}">
                                    {{ $e['name'] }}
                                    @if($e['election_date'])
                                        — {{ $e['election_date'] }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('election_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- CSV upload --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-600">
                            CSV File
                            <span class="text-[10px] text-slate-400">Max 10 MB</span>
                        </label>
                        <input type="file" wire:model="importFile"
                               class="mt-1 block w-full text-xs text-slate-600" />

                        @error('importFile')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        <p class="mt-1 text-[11px] text-slate-500">
                            Expected header (district totals):
                            <span class="font-mono">
                                district, turnout_pct, invalid_votes, valid_votes, total_votes, APC, SLPP, …
                            </span>
                            – party columns must match Party short codes.
                        </p>
                    </div>
                </div>

                {{-- Import button + status --}}
                <div class="flex items-center gap-3 pt-2">
                    <button wire:click="import"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800 disabled:opacity-50">
                        <span wire:loading.remove>Run Import</span>
                        <span wire:loading>Importing…</span>
                    </button>

                    @if($importMessage)
                        <span class="text-xs text-emerald-600">{{ $importMessage }}</span>
                    @endif
                </div>

                {{-- Errors list --}}
                @if($importErrors)
                    <div class="mt-4 border border-amber-200 bg-amber-50 rounded p-3 max-h-48 overflow-auto">
                        <p class="text-[11px] font-semibold text-amber-800 mb-1">
                            Warnings / Errors:
                        </p>
                        <ul class="text-[11px] text-amber-800 space-y-0.5">
                            @foreach($importErrors as $e)
                                <li>• {{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- Field glossary under import (same column) --}}
            <div class="bg-white border rounded-lg p-4 text-xs text-slate-600">
                <h2 class="text-xs font-semibold mb-2">Field glossary</h2>
                <dl class="grid md:grid-cols-2 gap-x-6 gap-y-1">
                    <div>
                        <dt class="font-semibold">district</dt>
                        <dd>Exact district name as defined in the Districts module (e.g. “Bo”, “Kono”).</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">total_votes</dt>
                        <dd>District-wide total valid votes. Used as turnout for synthetic station.</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">turnout_pct</dt>
                        <dd>Optional. Percentage turnout from ECSL sheet. Stored only in metadata later if needed.</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">APC, SLPP, …</dt>
                        <dd>Party vote totals. Column names must match <span class="font-mono">parties.short_code</span>.</dd>
                    </div>
                </dl>
            </div>

        </div>

        {{-- RIGHT COLUMN: Manual District Entry --}}
        <div class="space-y-4">
            <div class="bg-white border rounded-lg shadow-sm p-4 text-sm">
                <h2 class="text-sm font-semibold mb-1">Manual District Entry</h2>
                <p class="text-xs text-slate-600 mb-3">
                    Use this when you have district totals from PDFs or sheets
                    and want to input them quickly without preparing a CSV.
                    Stronghold creates one synthetic polling station per district.
                </p>

                {{-- District --}}
                <div class="mb-3">
                    <label class="block text-xs font-medium text-slate-600">District</label>
                    <select wire:model="manualDistrictId"
                            class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
                        <option value="">Select district…</option>
                        @foreach($districtOptions as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                    @error('manualDistrictId')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Registered voters --}}
                <div class="mb-3">
                    <label class="block text-xs font-medium text-slate-600">
                        Registered voters (optional)
                    </label>
                    <input type="number" min="0" wire:model.defer="manualRegistered"
                           class="mt-1 w-full border rounded-md px-3 py-2 text-sm"
                           placeholder="If unknown, leave blank. Stronghold will estimate from votes count." />
                    @error('manualRegistered')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Votes per party --}}
                <div class="mb-3">
                    <label class="block text-xs font-medium text-slate-600 mb-1">
                        Votes per party
                    </label>
                    <div class="space-y-2">
                        @foreach($parties as $party)
                            <div class="flex items-center gap-2">
                                <div class="w-32 text-[11px] text-slate-600">
                                    <span class="font-mono">{{ $party->short_code }}</span>
                                    @if($party->name)
                                        <span class="text-slate-400">({{ $party->name }})</span>
                                    @endif
                                </div>
                                <input type="number" min="0"
                                       wire:model.defer="manualVotes.{{ $party->id }}"
                                       class="flex-1 border rounded-md px-2 py-1.5 text-xs" />
                            </div>
                        @endforeach
                    </div>
                </div>

                <button wire:click="saveDistrictManual"
                        wire:loading.attr="disabled"
                        class="mt-2 inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800 disabled:opacity-50">
                    <span wire:loading.remove>Save District Results</span>
                    <span wire:loading>Saving…</span>
                </button>

                @if (session()->has('manual_status'))
                    <p class="mt-2 text-xs text-emerald-600">
                        {{ session('manual_status') }}
                    </p>
                @endif

                <p class="mt-3 text-[11px] text-slate-500">
                    Turnout is computed as the sum of all party votes you enter for this district.
                </p>
            </div>
        </div>

    </div>

    {{-- RESULTS TABLE --}}
    <div class="bg-white border rounded-lg p-4 text-sm space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold">
                Results overview
                @if($election_id)
                    @php
                        $e = collect($elections)->firstWhere('id', $election_id);
                    @endphp
                    @if($e)
                        — {{ $e['name'] }}
                    @endif
                @endif
            </h2>
            @if($election_id && ! $summaryRows)
                <span class="text-xs text-slate-500">
                    No results imported yet for this election.
                </span>
            @endif
        </div>

        @if($summaryRows)
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs border-t">
                    <thead>
                        <tr class="bg-slate-50 border-b">
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">District</th>
                            <th class="px-3 py-2 text-right font-semibold text-slate-700 whitespace-nowrap">
                                Total votes
                            </th>
                            @foreach($parties as $party)
                                <th class="px-3 py-2 text-right font-semibold text-slate-700 whitespace-nowrap">
                                    {{ $party->short_code }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summaryRows as $row)
                            <tr class="border-b last:border-0">
                                <td class="px-3 py-2 text-slate-800">
                                    {{ $row['district_name'] }}
                                </td>
                                <td class="px-3 py-2 text-right text-slate-800">
                                    {{ number_format($row['total_votes']) }}
                                </td>

                                @foreach($parties as $party)
                                    @php
                                        $votes = $row['party_votes'][$party->id] ?? 0;
                                        $pct   = $row['total_votes'] > 0
                                            ? round($votes * 100 / $row['total_votes'], 1)
                                            : 0;
                                    @endphp
                                    <td class="px-3 py-2 text-right text-slate-700">
                                        @if($votes > 0)
                                            {{ number_format($votes) }}
                                            <span class="text-[10px] text-slate-500">
                                                ({{ $pct }}%)
                                            </span>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
