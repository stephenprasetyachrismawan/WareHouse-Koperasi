<div class="space-y-6" wire:poll.60s>
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold">{{ __('Beranda') }}</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $warehouse?->name }}</p>
        </div>
        <x-dashboard.freshness :updated-at="$updatedAt" :warehouse="$warehouse" />
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <a href="{{ route('pickup.create') }}" wire:navigate
           class="inline-flex min-h-14 items-center justify-center rounded-lg bg-indigo-600 px-5 py-3 text-center text-base font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            {{ __('Request Barang') }}
        </a>
        <a href="{{ route('returns.create') }}" wire:navigate
           class="inline-flex min-h-14 items-center justify-center rounded-lg border border-zinc-300 bg-white px-5 py-3 text-center text-base font-semibold text-zinc-800 shadow-sm hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:hover:bg-zinc-700">
            {{ __('Retur Barang') }}
        </a>
    </div>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
        <a href="{{ route('pickup.my-requests') }}" wire:navigate class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm hover:border-indigo-300 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Siap Diambil') }}</p>
            <p class="mt-1 text-2xl font-semibold">{{ $readyPickups->count() }}</p>
            @if ($readyPickups->isEmpty())
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Belum ada pengambilan yang siap diambil.') }}</p>
            @endif
        </a>
        <a href="{{ route('inbox') }}" wire:navigate class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm hover:border-indigo-300 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Kotak Masuk') }}</p>
            <p class="mt-1 text-2xl font-semibold">{{ $unreadInboxCount }}</p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Notifikasi belum dibaca') }}</p>
        </a>
        <a href="{{ route('returns.my-returns') }}" wire:navigate class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm hover:border-indigo-300 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Penggantian Siap') }}</p>
            <p class="mt-1 text-2xl font-semibold">{{ $replacementReadyCount }}</p>
            @if ($replacementReadyCount === 0)
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Belum ada penggantian siap diambil.') }}</p>
            @endif
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <section class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800" aria-labelledby="latest-pickup-heading">
            <h3 id="latest-pickup-heading" class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Request Terbaru') }}</h3>
            @if ($latestPickup)
                <a href="{{ route('pickup.show', $latestPickup->uuid) }}" wire:navigate class="mt-3 block rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                    <p class="font-medium">{{ $latestPickup->request_number }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $latestPickup->status->label() }}</p>
                </a>
            @else
                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Belum ada request barang.') }}</p>
            @endif
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800" aria-labelledby="latest-return-heading">
            <h3 id="latest-return-heading" class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Retur Terbaru') }}</h3>
            @if ($latestReturn)
                <a href="{{ route('returns.show', $latestReturn->uuid) }}" wire:navigate class="mt-3 block rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                    <p class="font-medium">{{ $latestReturn->return_number }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $latestReturn->status->label() }}</p>
                </a>
            @else
                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Belum ada retur barang.') }}</p>
            @endif
        </section>
    </div>

    @if ($readyPickups->isNotEmpty())
        <section aria-labelledby="ready-pickups-heading">
            <h3 id="ready-pickups-heading" class="mb-2 text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Jadwal Pengambilan') }}</h3>
            <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                @foreach ($readyPickups as $pickup)
                    <a href="{{ route('pickup.show', $pickup->uuid) }}" wire:navigate class="flex min-h-12 items-center justify-between gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 {{ ! $loop->last ? 'border-b border-zinc-100 dark:border-zinc-700' : '' }}">
                        <span class="font-medium">{{ $pickup->request_number }}</span>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $pickup->ready_at?->format('d M Y H:i') }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
