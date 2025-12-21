{{-- File: resources/views/livewire/manage/results.blade.php --}}
<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">District Results</h1>
            <p class="text-sm text-slate-600">
                Aggregated vote totals per district and party for the selected election.
            </p>
        </div>
        @if (session('status'))
            <span class="text-xs text-green-700 bg-green-50 border border-green-100 rounded px-2 py-1.5">
                {{ session('status') }}
            </span>
        @endif
    </div>

    {{-- Filters --}}
    <div class="bg-white border rounded-lg p-4 text-sm flex flex-col md:flex-row gap-4">
        <div class="md:w-1/3">
            <label class="block text-xs font-medium text-slate-600">Election</label>
            <select wire:model.live.debounce.200ms="electionId"
                    class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
                <option value="">Select election…</option>
                @foreach($elections as $e)
                    <option value="{{ $e['id'] }}">
                        {{ $e['name'] }} @if($e['election_date']) — {{ $e['election_date'] }} @endif
                    </option>
                @endforeach
            </select>
        </div>

        <div class="md:w-1/3">
            <label class="block text-xs font-medium text-slate-600">District (optional)</label>
            <select wire:model.live.debounce.200ms="districtId"
                    class="mt-1 w-full border rounded-md px-3 py-2 text-sm"
                    @disabled(!$electionId)>
                <option value="">All districts</option>
                @foreach($districts as $d)
                    <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                @endforeach
            </select>
        </div>

        <div class="md:flex-1 flex items-end">
            <p class="text-[11px] text-slate-500">
                Results are aggregated from the <span class="font-mono">results</span> table.
            </p>
        </div>
    </div>

    {{-- Content: table + right sidebar form (like Populations) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Table --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg border p-0">
                <div class="px-4 py-3 border-b flex items-center justify-between text-sm text-slate-600">
                    <span>District-level votes</span>
                    <span class="text-xs text-slate-400">
                        @php $count = count($rows ?? []); @endphp
                        {{ $count }} district{{ $count === 1 ? '' : 's' }}
                    </span>
                </div>

                <div class="relative">
                    <div wire:loading class="absolute inset-x-0 top-0">
                        <div class="h-0.5 w-full bg-gradient-to-r from-slate-200 via-slate-400 to-slate-200 animate-pulse"></div>
                    </div>

                    @if(!$rows)
                        <div class="px-4 py-6 text-center text-slate-500 text-sm">
                            No results found for this filter.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs">
                                <thead>
                                    <tr class="bg-slate-50 border-b">
                                        <th class="px-3 py-2 text-left font-semibold text-slate-700">District</th>
                                        <th class="px-3 py-2 text-right font-semibold text-slate-700 whitespace-nowrap">Total votes</th>
                                        @foreach($parties as $party)
                                            <th class="px-3 py-2 text-right font-semibold text-slate-700 whitespace-nowrap">
                                                {{ $party['short_code'] }}
                                            </th>
                                        @endforeach
                                        <th class="px-3 py-2 text-right font-semibold text-slate-700"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($rows as $row)
                                        <tr wire:key="row-{{ $row['district_id'] }}">
                                            <td class="px-3 py-2 text-slate-800">
                                                {{ $row['district_name'] }}
                                            </td>
                                            <td class="px-3 py-2 text-right text-slate-800">
                                                {{ number_format($row['total_votes']) }}
                                            </td>
                                            @foreach($parties as $party)
                                                @php
                                                    $votes = $row['party_votes'][$party['id']] ?? 0;
                                                    $pct   = $row['total_votes'] > 0 ? round($votes * 100 / $row['total_votes'], 1) : 0;
                                                @endphp
                                                <td class="px-3 py-2 text-right text-slate-700" wire:key="cell-{{ $row['district_id'] }}-{{ $party['id'] }}">
                                                    @if($votes > 0)
                                                        {{ number_format($votes) }}
                                                        <span class="text-[10px] text-slate-500">({{ $pct }}%)</span>
                                                    @else
                                                        <span class="text-slate-300">—</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="px-3 py-2 text-right">
                                                <button type="button"
                                                        wire:click="openEditor({{ $row['district_id'] }})"
                                                        class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">
                                                    Edit
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right column: form (sidebar style) --}}
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm border p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">
                            {{ $editingDistrictId ? 'Edit District Totals' : 'Select a District' }}
                        </h2>
                        <p class="text-xs text-slate-500">
                            @if($editingDistrictId)
                                {{ $editingDistrictName }} — {{ optional(collect($elections)->firstWhere('id',$electionId))['name'] ?? '' }}
                            @else
                                Click <span class="font-medium">Edit</span> in the table to load district totals here.
                            @endif
                        </p>
                    </div>
                </div>

                @if($editingDistrictId)
                    <div class="bg-slate-50 border rounded p-3">
                        <div class="text-xs text-slate-600">Total (auto)</div>
                        <div class="text-lg font-semibold">{{ number_format($formTotal) }} votes</div>
                    </div>
                @endif

                <div class="space-y-3">
                    @foreach($parties as $p)
                        <div class="flex items-center justify-between gap-3" wire:key="edit-{{ $p['id'] }}">
                            <label class="text-sm text-slate-700 w-28">{{ $p['short_code'] }}</label>
                            <input
                                type="number" min="0" inputmode="numeric"
                                class="flex-1 border rounded px-3 py-2 text-sm"
                                wire:model.live.debounce.150ms="formVotes.{{ $p['id'] }}"
                                @disabled(!$editingDistrictId)
                                placeholder="0"
                            >
                            @error("formVotes.{$p['id']}")<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                        </div>
                    @endforeach

                    <div class="flex items-center justify-between pt-2">
                        <button type="button"
                                wire:click="createNew"
                                class="text-xs text-slate-500 hover:text-slate-700"
                                @disabled(!$editingDistrictId)>
                            Cancel
                        </button>

                        <button type="button"
                                wire:click="saveEditor"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center px-4 py-2 rounded bg-slate-900 text-white text-sm font-semibold hover:bg-black disabled:opacity-60"
                                @disabled(!$editingDistrictId)>
                            <span wire:loading.remove>Save totals</span>
                            <span wire:loading>Saving…</span>
                        </button>
                    </div>
                </div>

                <p class="text-[11px] text-slate-500">
                    Saving replaces prior rows for this district/party to prevent double counting.
                </p>
            </div>
        </div>
    </div>

    {{-- Small docs hint --}}
    <div class="pt-2 text-[11px] text-slate-500">
        Tip: Keep one consolidated total per district and party for a given election.
    </div>
</div>
