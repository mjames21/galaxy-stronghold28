{{-- resources/views/livewire/manage/districts.blade.php --}}
<div class="p-6 space-y-8">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Districts</h1>
            <p class="text-sm text-slate-600">
                Canonical district list for Stronghold 28. Used by results, populations and models.
            </p>
        </div>
        <button wire:click="createNew"
                class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
            New District
        </button>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Left: table --}}
        <div class="lg:col-span-2 bg-white border rounded-lg shadow-sm">
            <div class="px-4 py-3 border-b flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-700">District List</h2>
                <span class="text-xs text-slate-500">{{ count($districts) }} records</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-3 py-2 text-left">Name</th>
                            <th class="px-3 py-2 text-left">Code</th>
                            <th class="px-3 py-2 text-left">Region</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($districts as $d)
                            <tr class="border-t">
                                <td class="px-3 py-2">{{ $d['name'] }}</td>
                                <td class="px-3 py-2 font-mono text-xs">{{ $d['code'] }}</td>
                                <td class="px-3 py-2">{{ $d['region'] ?: '—' }}</td>
                                <td class="px-3 py-2 text-right">
                                    <button wire:click="edit({{ $d['id'] }})"
                                            class="text-xs font-semibold text-slate-700 hover:underline">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-4 text-center text-slate-500 text-sm">
                                    No districts yet. Use the form or import from CSV.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Right: form + import --}}
        <div class="space-y-6">
            {{-- Create / Edit --}}
            <div class="bg-white border rounded-lg shadow-sm p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">
                        {{ $editingId ? 'Edit District' : 'New District' }}
                    </h2>
                    @if (session('status'))
                        <span class="text-[11px] text-emerald-600">{{ session('status') }}</span>
                    @endif
                </div>

                <div class="space-y-3 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-slate-600">Name</label>
                        <input type="text" wire:model.defer="name"
                               class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600">Code</label>
                        <input type="text" wire:model.defer="code"
                               class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <p class="text-[11px] text-slate-500 mt-1">Short code e.g. <span class="font-mono">BO</span>, <span class="font-mono">WAU</span>.</p>
                        @error('code') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600">Region (optional)</label>
                        <input type="text" wire:model.defer="region"
                               class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <p class="text-[11px] text-slate-500 mt-1">e.g. North, South, East, West.</p>
                        @error('region') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="pt-2">
                        <button wire:click="save"
                                class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
                            Save District
                        </button>
                    </div>
                </div>
            </div>

            {{-- Import from CSV --}}
            <div class="bg-white border rounded-lg shadow-sm p-4 space-y-3 text-sm">
                <h2 class="text-sm font-semibold text-slate-700">Import from CSV</h2>
                <p class="text-xs text-slate-600">
                    Upload a CSV with columns:
                    <span class="font-mono">district, code, region</span>.
                    Existing districts (by name) will be updated.
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
