<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Laporan Operasional</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $warehouse->name }} · Data dibatasi pada Warehouse aktif.</p>
        </div>
        <x-dashboard.freshness :warehouse="$warehouse" />
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Laporan</label>
                <select wire:model.live="type" class="mt-1 w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-950">
                    @foreach ($reportTypes as $reportType)
                        <option value="{{ $reportType->value }}">{{ $reportType->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Item</label>
                <select wire:model.live="itemId" class="mt-1 w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-950">
                    <option value="">Semua item</option>
                    @foreach ($items as $item)
                        <option value="{{ $item->id }}">{{ $item->code }} — {{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            @if ($statusOptions !== [])
                <div>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Status</label>
                    <select wire:model.live="status" class="mt-1 w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-950">
                        <option value="">Semua status</option>
                        @foreach ($statusOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if ($sourceOptions !== [])
                <div>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Sumber</label>
                    <select wire:model.live="source" class="mt-1 w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-950">
                        <option value="">Semua sumber</option>
                        @foreach ($sourceOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if ($type === 'stock_movements')
                <div>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tipe mutasi</label>
                    <select wire:model.live="movementType" class="mt-1 w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-950">
                        <option value="">Semua tipe</option>
                        @foreach ($movementOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Dari ({{ $warehouse->timezone }})</label>
                <input type="date" wire:model.live="from" class="mt-1 w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-950" />
            </div>
            <div>
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Sampai ({{ $warehouse->timezone }})</label>
                <input type="date" wire:model.live="to" class="mt-1 w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-950" />
            </div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 text-left text-sm dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    @foreach ($columns as $label)
                        <th class="whitespace-nowrap px-4 py-3 font-semibold text-zinc-700 dark:text-zinc-300">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-800 dark:bg-zinc-950">
                @forelse ($report as $row)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        @foreach ($columns as $key => $label)
                            <td class="whitespace-nowrap px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $row->values[$key] ?? '—' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="px-6 py-10 text-center text-zinc-500 dark:text-zinc-400">Tidak ada data untuk filter yang dipilih.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>{{ $report->links() }}</div>
        @if ($canExport)
            <button type="button" wire:click="export" wire:loading.attr="disabled" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900">
                Export CSV
            </button>
        @endif
    </div>
</div>
