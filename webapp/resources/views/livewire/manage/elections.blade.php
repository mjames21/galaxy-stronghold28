{{-- resources/views/livewire/manage/elections.blade.php --}}
<div class="p-6 space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Elections</h1>
            <p class="text-sm text-slate-600">
                Define each election Stronghold 28 will model – by type, date, and round.
            </p>
        </div>

        <button wire:click="createNew"
                class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
            New Election
        </button>
    </div>

    @if (session('status'))
        <p class="text-xs text-emerald-600">{{ session('status') }}</p>
    @endif

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Table --}}
        <div class="lg:col-span-2 bg-white border rounded-lg shadow-sm">
            <div class="px-4 py-3 border-b flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-700">Election List</h2>
                <span class="text-xs text-slate-500">{{ count($rows) }} elections</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-3 py-2 text-left">Name</th>
                            <th class="px-3 py-2 text-left">Slug</th>
                            <th class="px-3 py-2 text-left">Date</th>
                            <th class="px-3 py-2 text-left">Type</th>
                            <th class="px-3 py-2 text-left">Round</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $e)
                            <tr class="border-t">
                                <td class="px-3 py-2">{{ $e['name'] }}</td>
                                <td class="px-3 py-2 font-mono text-xs">{{ $e['slug'] }}</td>
                                <td class="px-3 py-2">
                                    {{ $e['election_date'] ?? '—' }}
                                </td>
                                <td class="px-3 py-2">{{ $e['type'] }}</td>
                                <td class="px-3 py-2">{{ $e['round'] }}</td>
                                <td class="px-3 py-2 text-right">
                                    <button wire:click="edit({{ $e['id'] }})"
                                            class="text-xs font-semibold text-slate-700 hover:underline">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-4 text-center text-slate-500 text-sm">
                                    No elections defined yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Form + Import --}}
        <div class="space-y-6">
            {{-- Form --}}
            <div class="bg-white border rounded-lg shadow-sm p-4 space-y-4 text-sm">
                <h2 class="text-sm font-semibold text-slate-700">
                    {{ $editingId ? 'Edit Election' : 'New Election' }}
                </h2>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600">Name</label>
                        <input type="text" wire:model="name"
                               class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
                        <p class="text-[11px] text-slate-500 mt-1">
                            e.g. “2018 Presidential – First Round”.
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600">Slug</label>
                        <input type="text" wire:model="slug"
                               class="mt-1 w-full border rounded-md px-3 py-2 text-sm font-mono">
                        <p class="text-[11px] text-slate-500 mt-1">
                            Used in URLs and imports. e.g. <span class="font-mono">sl_2018_president_r1</span>
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Election Date</label>
                            <input type="date" wire:model="election_date"
                                   class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Round</label>
                            <input type="number" min="1" max="3" wire:model="round"
                                   class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600">Type</label>
                        <select wire:model="type"
                                class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
                            <option value="presidential">Presidential</option>
                            <option value="parliamentary">Parliamentary</option>
                            <option value="local">Local</option>
                        </select>
                    </div>

                    <div class="pt-2">
                        <button wire:click="save"
                                class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
                            Save Election
                        </button>
                    </div>
                </div>
            </div>

            {{-- Import --}}
            <div class="bg-white border rounded-lg shadow-sm p-4 space-y-3 text-sm">
                <h2 class="text-sm font-semibold text-slate-700">Import from CSV</h2>
                <p class="text-xs text-slate-600">
                    Columns:
                    <span class="font-mono">
                        name, slug, election_date, type, round
                    </span>.
                    Existing elections (same slug) will be updated.
                </p>

                <input type="file" wire:model="importFile"
                       class="mt-1 block w-full text-xs text-slate-600"/>

                @error('importFile') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                <button wire:click="import"
                        wire:loading.attr="disabled"
                        class="mt-2 inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded bg-slate-900 text-white hover:bg-slate-800 disabled:opacity-50">
                    <span wire:loading.remove>Run Import</span>
                    <span wire:loading>Importing…</span>
                </button>

                @if($importMessage)
                    <p class="text-xs text-emerald-600 mt-2">{{ $importMessage }}</p>
                @endif

                @if($importErrors)
                    <div class="mt-2 border border-amber-200 bg-amber-50 rounded p-2 max-h-32 overflow-auto">
                        <p class="text-[11px] font-semibold text-amber-800 mb-1">Warnings / Errors:</p>
                        <ul class="text-[11px] text-amber-800 space-y-0.5">
                            @foreach($importErrors as $e)
                                <li>• {{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
