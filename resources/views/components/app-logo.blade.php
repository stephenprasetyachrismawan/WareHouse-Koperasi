@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand :name="config('app.name', 'Laravel')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md">
            <img src="{{ asset('images/logo-kdmp.png') }}" alt="{{ config('app.name', 'Laravel') }}" class="size-full object-contain" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="config('app.name', 'Laravel')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md">
            <img src="{{ asset('images/logo-kdmp.png') }}" alt="{{ config('app.name', 'Laravel') }}" class="size-full object-contain" />
        </x-slot>
    </flux:brand>
@endif
