<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ __('Lokasi & Zona Rak Gudang') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Kelola struktur lokasi rak, zona, dan area penyimpanan barang di gudang.') }}
            </p>
        </div>
        <div>
            @can('create', App\Models\WarehouseLocation::class)
                <flux:button wire:click="$set('showCreateModal', true)" variant="primary" icon="plus">
                    {{ __('Tambah Lokasi') }}
                </flux:button>
            @endcan
        </div>
    </div>

    @if (session('status'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-zinc-800 dark:text-green-400" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <div class="w-full sm:w-80">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Cari kode atau lokasi...') }}" />
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-left text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300">
                <tr>
                    <th class="px-6 py-3 font-semibold">{{ __('Kode Area / Rak') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Nama Lokasi') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Deskripsi') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 bg-white dark:bg-zinc-950">
                @forelse ($locations as $loc)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="px-6 py-4 font-mono font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $loc->code }}
                        </td>
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $loc->name }}
                        </td>
                        <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                            {{ $loc->description ?? '-' }}
                        </td>
                        <td class="px-6 py-4">
                            @if ($loc->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ __('Aktif') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    {{ __('Non-Aktif') }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('Tidak ada lokasi rak ditemukan.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $locations->links() }}
    </div>

    <!-- Create Location Modal -->
    <flux:modal wire:model="showCreateModal" class="space-y-6">
        <div>
            <h2 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ __('Tambah Lokasi Rak Gudang') }}</h2>
            <p class="text-sm text-zinc-500">{{ __('Masukkan kode rak dan nama zona penyimpanan baru.') }}</p>
        </div>

        <form wire:submit="saveLocation" class="space-y-4">
            <flux:input wire:model="code" label="{{ __('Kode Rak / Area (e.g. RAK-A1)') }}" required />
            <flux:input wire:model="name" label="{{ __('Nama Lokasi Zona') }}" required placeholder="e.g. Rak Utama Minyak Goreng" />
            <flux:textarea wire:model="description" label="{{ __('Deskripsi Keterangan') }}" rows="2" />

            <div class="flex justify-end gap-2 pt-4">
                <flux:button wire:click="$set('showCreateModal', false)" variant="ghost">{{ __('Batal') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Simpan Lokasi') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
