<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Actions\Reports\CreateOperationalReportExportAction;
use App\Domain\Reports\Queries\OperationalReportQuery;
use App\Domain\Reports\ValueObjects\ReportFilters;
use App\Enums\MovementType;
use App\Enums\OperationalReportType;
use App\Enums\Permission;
use App\Enums\PickupRequestStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Enums\QualityInspectionResult;
use App\Enums\ReturnStatus;
use App\Enums\WarehouseRole;
use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $type = 'stock';

    public string $status = '';

    public string $source = '';

    public string $movementType = '';

    public string $itemId = '';

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        abort_if($actor->isSuperAdmin(), 403);

        $membership = $actor->activeMembership();
        abort_if($membership === null, 403);
        abort_unless($this->hasReportsPermission($membership), 403);

        $this->type = (string) request()->query('type', $this->type);
        $this->status = (string) request()->query('status', '');
        $this->source = (string) request()->query('source', '');
        $this->movementType = (string) request()->query('movement_type', '');
        $this->itemId = (string) request()->query('item_id', '');
        $this->from = (string) request()->query('from', now($membership->warehouse->timezone)->subDays(30)->format('Y-m-d'));
        $this->to = (string) request()->query('to', now($membership->warehouse->timezone)->format('Y-m-d'));

        abort_unless($this->canViewType($membership, $this->selectedType()), 403);
    }

    public function updatingType(): void
    {
        $this->resetPage();
        $this->status = '';
        $this->source = '';
        $this->movementType = '';
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingSource(): void
    {
        $this->resetPage();
    }

    public function updatingMovementType(): void
    {
        $this->resetPage();
    }

    public function updatingItemId(): void
    {
        $this->resetPage();
    }

    public function updatingFrom(): void
    {
        $this->resetPage();
    }

    public function updatingTo(): void
    {
        $this->resetPage();
    }

    public function export(): mixed
    {
        /** @var User $actor */
        $actor = Auth::user();
        $membership = $actor->activeMembership();
        abort_if($membership === null, 403);
        $type = $this->selectedType();
        abort_unless($this->canExport($membership, $type), 403);

        /** @var Warehouse $warehouse */
        $warehouse = $membership->warehouse;
        $filters = $this->filters($warehouse, $type);
        $export = app(CreateOperationalReportExportAction::class)->create($warehouse, $actor->id, $filters);

        return redirect()->route('reports.exports.download', $export);
    }

    public function render(): View
    {
        /** @var User $actor */
        $actor = Auth::user();
        $membership = $actor->activeMembership();
        abort_if($membership === null, 403);

        $selectedType = $this->selectedType();
        abort_unless($this->canViewType($membership, $selectedType), 403);

        /** @var Warehouse $warehouse */
        $warehouse = $membership->warehouse;
        $filters = $this->filters($warehouse, $selectedType);

        return view('livewire.reports.index', [
            'warehouse' => $warehouse,
            'report' => app(OperationalReportQuery::class)->execute($warehouse, $filters),
            'items' => Item::query()->forWarehouse($warehouse->id)->orderBy('name')->get(['id', 'code', 'name']),
            'reportTypes' => $this->availableTypes($membership),
            'columns' => $this->columns($selectedType),
            'statusOptions' => $this->statusOptions($selectedType),
            'movementOptions' => array_map(fn (MovementType $type): array => ['value' => $type->value, 'label' => $type->label()], MovementType::cases()),
            'sourceOptions' => $this->sourceOptions($selectedType),
            'canExport' => $this->canExport($membership, $selectedType),
        ]);
    }

    private function filters(Warehouse $warehouse, OperationalReportType $type): ReportFilters
    {
        return ReportFilters::fromInput(
            type: $type,
            itemId: is_numeric($this->itemId) ? (int) $this->itemId : null,
            status: $this->supportedValue($this->status, array_column($this->statusOptions($type), 'value')),
            source: $this->supportedValue($this->source, array_column($this->sourceOptions($type), 'value')),
            movementType: $this->supportedValue($this->movementType, array_map(fn (MovementType $movementType): string => $movementType->value, MovementType::cases())),
            from: $this->from,
            to: $this->to,
            timezone: $warehouse->timezone,
        );
    }

    private function selectedType(): OperationalReportType
    {
        return OperationalReportType::tryFrom($this->type) ?? OperationalReportType::Stock;
    }

    /** @return array<string, string> */
    private function columns(OperationalReportType $type): array
    {
        return match ($type) {
            OperationalReportType::Stock => [
                'code' => 'Kode', 'item' => 'Item', 'quantity' => 'Saldo', 'minimum_stock' => 'Minimum', 'critical' => 'Kritis',
            ],
            OperationalReportType::StockMovements => [
                'occurred_at' => 'Waktu', 'item' => 'Item', 'movement_type' => 'Tipe', 'signed_quantity' => 'Jumlah', 'source' => 'Sumber', 'reference' => 'Referensi', 'actor' => 'Aktor',
            ],
            OperationalReportType::PurchaseRequests => [
                'request_number' => 'Nomor PR', 'item' => 'Item', 'quantity' => 'Jumlah', 'source' => 'Sumber', 'urgency' => 'Urgensi', 'status' => 'Status', 'created_at' => 'Dibuat', 'terminal_at' => 'Terminal',
            ],
            OperationalReportType::PurchaseOrders => [
                'po_number' => 'Nomor PO', 'supplier' => 'Supplier', 'item' => 'Item', 'quantity' => 'Jumlah', 'status' => 'Status', 'created_at' => 'Dibuat', 'sent_at' => 'Dikirim', 'received' => 'Diterima',
            ],
            OperationalReportType::Pickups => [
                'request_number' => 'Nomor Pickup', 'koperasi' => 'Koperasi', 'item' => 'Item', 'quantity' => 'Jumlah', 'status' => 'Status', 'requested_at' => 'Diminta', 'scheduled_at' => 'Siap/Jadwal', 'completed_at' => 'Selesai',
            ],
            OperationalReportType::Returns => [
                'return_number' => 'Nomor Return', 'koperasi' => 'Koperasi', 'item' => 'Item', 'quantity' => 'Jumlah', 'status' => 'Status', 'submitted_at' => 'Diajukan', 'decision_at' => 'Keputusan', 'replacement' => 'Replacement',
            ],
            OperationalReportType::QualityControl => [
                'receipt_number' => 'Nomor Receipt', 'po_number' => 'Nomor PO', 'supplier' => 'Supplier', 'item' => 'Item', 'result' => 'Hasil QC', 'inspector' => 'Inspektor', 'inspected_at' => 'Diperiksa',
            ],
        };
    }

    /** @return list<array{value: string, label: string}> */
    private function statusOptions(OperationalReportType $type): array
    {
        $cases = match ($type) {
            OperationalReportType::PurchaseRequests => PurchaseRequestStatus::cases(),
            OperationalReportType::PurchaseOrders => PurchaseOrderStatus::cases(),
            OperationalReportType::Pickups => PickupRequestStatus::cases(),
            OperationalReportType::Returns => ReturnStatus::cases(),
            OperationalReportType::QualityControl => QualityInspectionResult::cases(),
            default => [],
        };

        return array_map(fn ($status): array => ['value' => $status->value, 'label' => $status->label()], $cases);
    }

    /** @return list<array{value: string, label: string}> */
    private function sourceOptions(OperationalReportType $type): array
    {
        return $type === OperationalReportType::PurchaseRequests
            ? [['value' => 'MANUAL', 'label' => 'Manual'], ['value' => 'CRITICAL_STOCK', 'label' => 'Stok Kritis'], ['value' => 'COOPERATIVE_BACKORDER', 'label' => 'Backorder Koperasi'], ['value' => 'RETURN_REPLACEMENT', 'label' => 'Replacement Return']]
            : [];
    }

    /** @param list<string> $allowed */
    private function supportedValue(string $value, array $allowed): ?string
    {
        return in_array($value, $allowed, true) ? $value : null;
    }

    /** @return list<OperationalReportType> */
    private function availableTypes(WarehouseMembership $membership): array
    {
        return array_values(array_filter(OperationalReportType::cases(), fn (OperationalReportType $type): bool => $this->canViewType($membership, $type)));
    }

    private function hasReportsPermission(WarehouseMembership $membership): bool
    {
        return $this->permission($membership, Permission::ReportsView);
    }

    private function canViewType(WarehouseMembership $membership, OperationalReportType $type): bool
    {
        $permission = match ($type) {
            OperationalReportType::Stock => Permission::StockView,
            OperationalReportType::StockMovements => Permission::StockLedgerView,
            OperationalReportType::PurchaseRequests => Permission::PurchaseRequestViewAny,
            OperationalReportType::PurchaseOrders => Permission::PurchaseOrderViewAny,
            OperationalReportType::Pickups => Permission::PickupRequestViewAny,
            OperationalReportType::Returns => Permission::ReturnViewAny,
            OperationalReportType::QualityControl => Permission::ReceiptViewAny,
        };

        return $this->permission($membership, $permission);
    }

    private function canExport(WarehouseMembership $membership, OperationalReportType $type): bool
    {
        return $this->canViewType($membership, $type) && $this->permission($membership, Permission::ReportsExport);
    }

    private function permission(WarehouseMembership $membership, Permission $permission): bool
    {
        $role = $membership->role instanceof WarehouseRole ? $membership->role : WarehouseRole::tryFrom((string) $membership->role);

        if ($role === WarehouseRole::AppAdmin) {
            return in_array($permission->value, $membership->permissions ?? [], true)
                && in_array(Permission::ReportsView->value, $membership->permissions ?? [], true);
        }

        return $membership->hasPermission($permission);
    }
}
