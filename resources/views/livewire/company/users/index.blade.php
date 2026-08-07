<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ __('Manajemen Pengguna Tenant') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Kelola akun pengguna, peran, dan status akses internal untuk ') }} <strong>{{ $company?->name }}</strong>
            </p>
        </div>
        <div>
            <flux:button href="{{ route('company.users.create') }}" variant="primary" icon="user-plus" wire:navigate>
                {{ __('Tambah Pengguna') }}
            </flux:button>
        </div>
    </div>

    @if (session('status'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-zinc-800 dark:text-green-400" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-4">
        <div class="w-full max-w-sm">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Cari nama atau email...') }}" />
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-left text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300">
                <tr>
                    <th class="px-6 py-3 font-semibold">{{ __('Nama') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Email') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Role') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Gudang') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Status') }}</th>
                    <th class="px-6 py-3 font-semibold text-right">{{ __('Aksi') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 bg-white dark:bg-zinc-950">
                @forelse ($memberships as $membership)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $membership->user->name }}
                        </td>
                        <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                            {{ $membership->user->email }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ strtoupper($membership->role) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                            {{ $membership->warehouse?->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4">
                            @if ($membership->status === 'active')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ __('Aktif') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    {{ __('Non-Aktif') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <flux:button href="{{ route('company.users.edit', $membership->user) }}" size="sm" variant="ghost" icon="pencil-square" wire:navigate>
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button wire:click="toggleStatus({{ $membership->user->id }})" size="sm" variant="{{ $membership->status === 'active' ? 'danger' : 'subtle' }}">
                                {{ $membership->status === 'active' ? __('Non-aktifkan') : __('Aktifkan') }}
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('Tidak ada pengguna ditemukan.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $memberships->links() }}
    </div>
</div>
