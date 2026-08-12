<?php

use App\Actions\Notifications\RevokeDeviceTokenAction;
use App\Models\DeviceToken;
use App\Models\NotificationPreference;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Notification settings')] class extends Component {
    public array $devices = [];

    public bool $pushEnabled = true;

    public function mount(): void
    {
        $this->loadDevices();
        $this->loadPreference();
    }

    #[On('device-registered')]
    public function loadDevices(): void
    {
        $this->devices = DeviceToken::forUser(Auth::id())->active()
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn (DeviceToken $device) => [
                'uuid' => $device->uuid,
                'device_name' => $device->device_name ?? __('Unknown device'),
                'last_seen_diff' => $device->last_seen_at?->diffForHumans(),
            ])
            ->toArray();
    }

    public function loadPreference(): void
    {
        $preference = NotificationPreference::where('user_id', Auth::id())
            ->whereNull('warehouse_id')
            ->whereNull('notification_type')
            ->where('channel', 'push')
            ->first();

        $this->pushEnabled = $preference?->enabled ?? true;
    }

    public function updatedPushEnabled(bool $value): void
    {
        NotificationPreference::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'warehouse_id' => null,
                'notification_type' => null,
                'channel' => 'push',
            ],
            ['enabled' => $value]
        );

        Flux::toast(text: $value ? __('Push notifications enabled.') : __('Push notifications disabled.'));
    }

    public function revokeDevice(string $uuid, RevokeDeviceTokenAction $action): void
    {
        $deviceToken = DeviceToken::where('uuid', $uuid)->firstOrFail();

        $action->execute(Auth::user(), $deviceToken);

        $this->loadDevices();

        Flux::toast(text: __('Device removed.'));
    }
}; ?>

<section class="w-full">
    {{-- Livewire requires a single root element per component — this must
         stay nested inside <section>, not rendered as a sibling before it. --}}
    @vite('resources/js/push.js')

    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Notification settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Notifications')" :subheading="__('Manage browser push notifications and registered devices')">
        <div
            x-data="{
                status: 'unsupported',
                loading: false,
                error: null,
                init() {
                    const setStatus = () => { this.status = window.WarehousePush?.getPushPermissionStatus() ?? 'unsupported' }
                    if (window.WarehousePush) { setStatus() } else { window.addEventListener('push:ready', setStatus, { once: true }) }
                },
                async enable() {
                    this.loading = true
                    this.error = null
                    try {
                        const result = await window.WarehousePush.enablePushNotifications()
                        this.status = result.status
                        if (result.status === 'granted') {
                            $wire.dispatch('device-registered')
                        }
                    } catch (e) {
                        this.error = e.message
                    } finally {
                        this.loading = false
                    }
                },
            }"
            class="space-y-4"
        >
            <flux:heading size="lg">{{ __('Browser Push Notifications') }}</flux:heading>
            <flux:subheading>{{ __('Get notified in your browser when something needs your decision — even when this tab is closed.') }}</flux:subheading>

            <div wire:cloak>
                <template x-if="status === 'granted'">
                    <flux:badge color="lime">{{ __('Enabled on this device') }}</flux:badge>
                </template>
                <template x-if="status === 'denied'">
                    <flux:text variant="subtle">{{ __('You have blocked notifications for this site in your browser settings.') }}</flux:text>
                </template>
                <template x-if="status === 'default'">
                    <flux:button variant="primary" x-on:click="enable" x-bind:disabled="loading">
                        <span x-show="!loading">{{ __('Enable Push Notifications') }}</span>
                        <span x-show="loading" x-cloak>{{ __('Enabling…') }}</span>
                    </flux:button>
                </template>
                <template x-if="status === 'unsupported'">
                    <flux:text variant="subtle">{{ __('Push notifications are not supported in this browser.') }}</flux:text>
                </template>

                <flux:text x-show="error" x-text="error" class="text-red-600 mt-2" x-cloak></flux:text>
            </div>
        </div>

        <flux:separator class="my-8" />

        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="lg">{{ __('Approval & Cancellation Push') }}</flux:heading>
                <flux:subheading>{{ __('Receive a push notification for approval and cancellation decisions that need you.') }}</flux:subheading>
            </div>
            <flux:switch wire:model.live="pushEnabled" />
        </div>

        <flux:separator class="my-8" />

        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Registered Devices') }}</flux:heading>

            <div class="border rounded-lg border-zinc-200 dark:border-zinc-700 overflow-hidden">
                @forelse ($devices as $device)
                    <div class="flex items-center justify-between p-4 {{ ! $loop->last ? 'border-b border-zinc-200 dark:border-zinc-700' : '' }}">
                        <div>
                            <p class="font-medium tracking-tight">{{ $device['device_name'] }}</p>
                            @if ($device['last_seen_diff'])
                                <p class="text-zinc-500 dark:text-zinc-400 text-xs">
                                    {{ __('Last seen :time', ['time' => $device['last_seen_diff']]) }}
                                </p>
                            @endif
                        </div>

                        <flux:button
                            variant="ghost"
                            size="sm"
                            icon="trash"
                            icon:variant="outline"
                            wire:click="revokeDevice('{{ $device['uuid'] }}')"
                            class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50"
                        />
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <p class="font-medium">{{ __('No devices registered') }}</p>
                        <flux:text class="mt-1">{{ __('Enable push notifications above to register this device.') }}</flux:text>
                    </div>
                @endforelse
            </div>
        </div>
    </x-pages::settings.layout>
</section>
