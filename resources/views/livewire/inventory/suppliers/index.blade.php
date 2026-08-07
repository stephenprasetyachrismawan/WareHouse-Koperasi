<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ __('Master Supplier / Pemasok') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Kelola data pendaftaran mitra supplier gudang aktif.') }}
            </p>
        </div>
        <div>
            @can('create', App\Models\Supplier::class)
                <flux:button wire:click="$set('showCreateModal', true)" variant="primary" icon="plus">
                    {{ __('Tambah Supplier') }}
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
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Cari supplier...') }}" />
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-left text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300">
                <tr>
                    <th class="px-6 py-3 font-semibold">{{ __('Nama Supplier') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Kontak Persona') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Email') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Telepon') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Alamat') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 bg-white dark:bg-zinc-950">
                @forelse ($suppliers as $sup)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $sup->name }}
                        </td>
                        <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                            {{ $sup->contact_name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                            {{ $sup->email ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                            {{ $sup->phone ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400 max-w-xs truncate">
                            {{ $sup->address ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('Tidak ada supplier ditemukan.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $suppliers->links() }}
    </div>

    <!-- Create Supplier Modal -->
    <flux:modal wire:model="showCreateModal" class="space-y-6">
        <div>
            <h2 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ __('Tambah Supplier Baru') }}</h2>
            <p class="text-sm text-zinc-500">{{ __('Masukkan detail supplier pendaftaran baru.') }}</p>
        </div>

        <form wire:submit="saveSupplier" class="space-y-4">
            <flux:input wire:model="name" label="{{ __('Nama Supplier / Perusahaan') }}" required />
            <flux:input wire:model="contact_name" label="{{ __('Nama Kontak / Sales') }}" />
            <flux:input wire:model="email" type="email" label="{{ __('Email Kontak') }}" />
            <flux:input wire:model="phone" label="{{ __('Nomor Telepon') }}" />
            <flux:textarea wire:model="address" label="{{ __('Alamat Lengkap') }}" rows="2" />

            <div class="flex justify-end gap-2 pt-4">
                <flux:button wire:click="$set('showCreateModal', false)" variant="ghost">{{ __('Batal') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Simpan Supplier') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
