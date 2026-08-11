<?php

namespace Database\Factories;

use App\Enums\NotificationType;
use App\Models\InboxNotification;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class InboxNotificationFactory extends Factory
{
    protected $model = InboxNotification::class;

    public function definition(): array
    {
        return [
            'recipient_id' => User::factory(),
            'warehouse_id' => Warehouse::factory(),
            'type' => NotificationType::PurchaseRequestStatus,
            'title' => 'Status Purchase Request',
            'message' => $this->faker->sentence(),
            'correlation_key' => $this->faker->unique()->uuid(),
            'read_at' => null,
        ];
    }

    public function unread(): self
    {
        return $this->state(fn (array $attributes) => ['read_at' => null]);
    }

    public function read(): self
    {
        return $this->state(fn (array $attributes) => ['read_at' => now()]);
    }

    public function approvalRequired(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => NotificationType::ApprovalRequired,
            'title' => 'Persetujuan Diperlukan',
            'message' => 'Ada permintaan baru yang menunggu persetujuan Anda.',
        ]);
    }

    public function readyForPickup(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => NotificationType::ReadyForPickup,
            'title' => 'Barang Siap Diambil',
            'message' => 'Pesanan Anda sudah siap diambil di gudang.',
        ]);
    }

    public function returnStatus(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => NotificationType::ReturnStatus,
            'title' => 'Status Retur Diperbarui',
            'message' => 'Retur Anda telah diproses.',
        ]);
    }

    public function replacementReady(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => NotificationType::ReplacementReady,
            'title' => 'Penggantian Siap Diambil',
            'message' => 'Barang penggantian retur Anda sudah siap diambil.',
        ]);
    }
}
