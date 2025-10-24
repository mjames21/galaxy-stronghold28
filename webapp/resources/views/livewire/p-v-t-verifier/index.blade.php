<div class="p-6 space-y-6">
    <h1 class="text-2xl font-semibold">PVT Verifier</h1>
    <p class="text-slate-600">Upload observer tallies to verify results in real time.</p>

    <div class="border rounded p-4 bg-white">
        <label class="block text-sm font-medium text-slate-600">Upload CSV</label>
        <input type="file" wire:model="csv" class="mt-2">
        @error('csv') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror

        <button wire:click="upload" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
            Process PVT CSV
        </button>
    </div>

    @if($summary)
        <div class="p-4 border rounded bg-white">
            <h2 class="text-lg font-semibold">Verification Summary</h2>
            <pre class="text-xs whitespace-pre-wrap">{{ json_encode($summary, JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endif
</div>
