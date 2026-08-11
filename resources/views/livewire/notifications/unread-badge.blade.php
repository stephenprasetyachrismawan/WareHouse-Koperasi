<a href="{{ route('inbox') }}" wire:navigate class="relative inline-flex items-center p-2 text-zinc-500 hover:text-zinc-800 dark:hover:text-white" title="Kotak Masuk">
    <flux:icon name="bell" class="size-5" />
    @if ($unreadCount > 0)
        <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center min-w-[1.1rem] h-[1.1rem] px-1 text-[10px] font-semibold rounded-full bg-red-500 text-white">
            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
        </span>
    @endif
</a>
