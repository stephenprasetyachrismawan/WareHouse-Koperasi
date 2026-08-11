<div>
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Kotak Masuk</h2>
        @if ($unreadCount > 0)
            <flux:button size="sm" wire:click="markAllRead">Tandai Semua Dibaca</flux:button>
        @endif
    </div>

    <div class="flex gap-2 mb-4">
        <flux:button size="sm" :variant="$filter === 'all' ? 'primary' : 'ghost'" wire:click="setFilter('all')">
            Semua
        </flux:button>
        <flux:button size="sm" :variant="$filter === 'unread' ? 'primary' : 'ghost'" wire:click="setFilter('unread')">
            Belum Dibaca ({{ $unreadCount }})
        </flux:button>
    </div>

    <div class="space-y-2">
        @forelse ($notifications as $notification)
            <div class="bg-white rounded shadow-sm p-4 {{ $notification->isUnread() ? 'border-l-4 border-indigo-500' : '' }}">
                <div class="flex justify-between items-start gap-3">
                    <div class="flex-1">
                        <p class="font-semibold {{ $notification->isUnread() ? 'text-gray-900' : 'text-gray-600' }}">
                            {{ $notification->title }}
                        </p>
                        <p class="text-sm text-gray-600 mt-1">{{ $notification->message }}</p>
                        <p class="text-xs text-gray-400 mt-2">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    @if ($notification->isUnread())
                        <span class="w-2 h-2 rounded-full bg-indigo-500 mt-1"></span>
                    @endif
                </div>

                <div class="mt-3 flex gap-3">
                    @if ($notification->action_route)
                        <a href="{{ $notification->action_route }}"
                           wire:click="markRead({{ $notification->id }})"
                           class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">
                            Lihat Detail &rarr;
                        </a>
                    @endif
                    @if ($notification->isUnread())
                        <button wire:click="markRead({{ $notification->id }})" class="text-sm text-gray-500 hover:text-gray-700">
                            Tandai Dibaca
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded shadow-sm p-4 text-center text-sm text-gray-500">
                Tidak ada notifikasi.
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $notifications->links() }}</div>
</div>
