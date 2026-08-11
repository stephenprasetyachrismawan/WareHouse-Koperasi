<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="bell" :href="route('inbox')" :current="request()->routeIs('inbox')" wire:navigate>
                        {{ __('Kotak Masuk') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                @if(auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->isAppAdmin() || auth()->user()->can('users.view')))
                    <flux:sidebar.group :heading="__('Administrasi Tenant')" class="grid">
                        <flux:sidebar.item icon="users" :href="route('company.users.index')" :current="request()->routeIs('company.users.*')" wire:navigate>
                            {{ __('Manajemen Pengguna') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif

                @if(auth()->check())
                    <flux:sidebar.group :heading="__('Inventaris & Gudang')" class="grid">
                        @can('viewAny', App\Models\Item::class)
                            <flux:sidebar.item icon="cube" :href="route('inventory.items.index')" :current="request()->routeIs('inventory.items.*')" wire:navigate>
                                {{ __('Katalog Barang') }}
                            </flux:sidebar.item>
                        @endcan
                        @can('viewAny', App\Models\StockBalance::class)
                            <flux:sidebar.item icon="circle-stack" :href="route('inventory.stock.overview')" :current="request()->routeIs('inventory.stock.overview') || request()->routeIs('inventory.stock.movement')" wire:navigate>
                                {{ __('Saldo & Transaksi Stok') }}
                            </flux:sidebar.item>
                        @endcan
                        @can('viewAny', App\Models\StockTransaction::class)
                            <flux:sidebar.item icon="document-text" :href="route('inventory.stock.ledger')" :current="request()->routeIs('inventory.stock.ledger')" wire:navigate>
                                {{ __('Ledger Transaksi') }}
                            </flux:sidebar.item>
                        @endcan
                        @can('viewAny', App\Models\Supplier::class)
                            <flux:sidebar.item icon="truck" :href="route('inventory.suppliers.index')" :current="request()->routeIs('inventory.suppliers.*')" wire:navigate>
                                {{ __('Master Supplier') }}
                            </flux:sidebar.item>
                        @endcan
                        @can('viewAny', App\Models\WarehouseLocation::class)
                            <flux:sidebar.item icon="building-office-2" :href="route('inventory.locations.index')" :current="request()->routeIs('inventory.locations.*')" wire:navigate>
                                {{ __('Lokasi & Rak') }}
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>

                    <flux:sidebar.group :heading="__('Pengambilan Koperasi')" class="grid">
                        <flux:sidebar.item icon="shopping-cart" :href="route('pickup.my-requests')" :current="request()->routeIs('pickup.my-requests') || request()->routeIs('pickup.create')" wire:navigate>
                            {{ __('Request Saya') }}
                        </flux:sidebar.item>
                        
                        @can('approve', App\Models\PickupRequest::class)
                            <flux:sidebar.item icon="check-badge" :href="route('pickup.approval-inbox')" :current="request()->routeIs('pickup.approval-inbox')" wire:navigate>
                                {{ __('Approval Pickup') }}
                            </flux:sidebar.item>
                        @endcan
                        
                        @can('prepare', App\Models\PickupRequest::class)
                            <flux:sidebar.item icon="inbox-arrow-down" :href="route('pickup.fulfilment')" :current="request()->routeIs('pickup.fulfilment')" wire:navigate>
                                {{ __('Penyiapan & Pickup') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('create', App\Models\ReturnRequest::class)
                            <flux:sidebar.item icon="arrow-uturn-left" :href="route('returns.my-returns')" :current="request()->routeIs('returns.my-returns') || request()->routeIs('returns.create') || request()->routeIs('returns.show')" wire:navigate>
                                {{ __('Retur Barang') }}
                            </flux:sidebar.item>
                        @endcan

                        @if(auth()->user()->can('returns.verify'))
                            <flux:sidebar.item icon="magnifying-glass-circle" :href="route('returns.verification-queue')" :current="request()->routeIs('returns.verification-queue')" wire:navigate>
                                {{ __('Verifikasi Retur') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="arrow-path" :href="route('returns.replacement-tasks')" :current="request()->routeIs('returns.replacement-tasks')" wire:navigate>
                                {{ __('Tugas Penggantian Retur') }}
                            </flux:sidebar.item>
                        @endif

                        @can('approve', App\Models\ReturnRequest::class)
                            <flux:sidebar.item icon="clipboard-document-check" :href="route('returns.approval-queue')" :current="request()->routeIs('returns.approval-queue')" wire:navigate>
                                {{ __('Keputusan Retur') }}
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>

                    <flux:sidebar.group :heading="__('Pengadaan & Purchase')" class="grid">
                        @can('viewAny', App\Models\PurchaseRequest::class)
                            <flux:sidebar.item icon="document-duplicate" :href="route('procurement.index')" :current="request()->routeIs('procurement.index') || request()->routeIs('procurement.create')" wire:navigate>
                                {{ __('Purchase Request') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('approve', App\Models\PurchaseRequest::class)
                            <flux:sidebar.item icon="check-badge" :href="route('procurement.approval-inbox')" :current="request()->routeIs('procurement.approval-inbox')" wire:navigate>
                                {{ __('Approval Procurement') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('viewAny', App\Models\PurchaseRequest::class)
                            <flux:sidebar.item icon="queue-list" :href="route('procurement.approved-queue')" :current="request()->routeIs('procurement.approved-queue')" wire:navigate>
                                {{ __('Approved Queue') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('create', App\Models\PurchaseRequestGroup::class)
                            <flux:sidebar.item icon="squares-plus" :href="route('procurement.grouping')" :current="request()->routeIs('procurement.grouping')" wire:navigate>
                                {{ __('Grouping & PO') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('viewAny', App\Models\PurchaseOrder::class)
                            <flux:sidebar.item icon="clipboard-document-list" :href="route('procurement.purchase-orders.index')" :current="request()->routeIs('procurement.purchase-orders.*')" wire:navigate>
                                {{ __('Purchase Orders') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('viewAny', App\Models\GoodsReceipt::class)
                            <flux:sidebar.item icon="truck" :href="route('procurement.receipts.index')" :current="request()->routeIs('procurement.receipts.*')" wire:navigate>
                                {{ __('Receipts') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('viewAny', App\Models\GoodsReceipt::class)
                            @if(auth()->user()->can('receipts.qc'))
                                <flux:sidebar.item icon="magnifying-glass-circle" :href="route('procurement.qc-queue')" :current="request()->routeIs('procurement.qc-queue')" wire:navigate>
                                    {{ __('Antrean QC') }}
                                </flux:sidebar.item>
                            @endif
                        @endcan
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>


            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <livewire:notifications.unread-badge />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
