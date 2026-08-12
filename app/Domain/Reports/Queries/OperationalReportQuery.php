<?php

declare(strict_types=1);

namespace App\Domain\Reports\Queries;

use App\Domain\Reports\ValueObjects\OperationalReportRow;
use App\Domain\Reports\ValueObjects\ReportFilters;
use App\Enums\OperationalReportType;
use App\Enums\PurchaseRequestStatus;
use App\Models\Item;
use App\Models\PickupRequestItem;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequestItem;
use App\Models\QualityInspection;
use App\Models\ReturnRequestItem;
use App\Models\StockTransaction;
use App\Models\Warehouse;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

final class OperationalReportQuery
{
    /** @return LengthAwarePaginator<int, OperationalReportRow> */
    public function execute(Warehouse $warehouse, ReportFilters $filters, int $perPage = 20, int $page = 1): LengthAwarePaginator
    {
        $paginator = match ($filters->type) {
            OperationalReportType::Stock => $this->stock($warehouse, $filters, $perPage, $page),
            OperationalReportType::StockMovements => $this->stockMovements($warehouse, $filters, $perPage, $page),
            OperationalReportType::PurchaseRequests => $this->purchaseRequests($warehouse, $filters, $perPage, $page),
            OperationalReportType::PurchaseOrders => $this->purchaseOrders($warehouse, $filters, $perPage, $page),
            OperationalReportType::Pickups => $this->pickups($warehouse, $filters, $perPage, $page),
            OperationalReportType::Returns => $this->returns($warehouse, $filters, $perPage, $page),
            OperationalReportType::QualityControl => $this->qualityControl($warehouse, $filters, $perPage, $page),
        };

        return $paginator;
    }

    /** @return iterable<int, OperationalReportRow> */
    public function export(Warehouse $warehouse, ReportFilters $filters): iterable
    {
        $page = 1;

        do {
            $paginator = $this->execute($warehouse, $filters, 500, $page);

            foreach ($paginator->items() as $row) {
                yield $row;
            }

            $page++;
        } while ($page <= $paginator->lastPage());
    }

    /** @return LengthAwarePaginator<int, OperationalReportRow> */
    private function stock(Warehouse $warehouse, ReportFilters $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $query = Item::query()
            ->forWarehouse($warehouse->id)
            ->with('stockBalance')
            ->when($filters->itemId, fn (Builder $query, int $itemId) => $query->whereKey($itemId))
            ->orderBy('name')
            ->orderBy('id');

        return $this->paginate($query, function (Item $item): OperationalReportRow {
            $quantity = $item->stockBalance === null ? 0 : $item->stockBalance->quantity;

            return new OperationalReportRow([
                'item' => $item->name,
                'code' => $item->code,
                'quantity' => $quantity,
                'minimum_stock' => $item->minimum_stock,
                'critical' => $quantity < $item->minimum_stock ? 'Ya' : 'Tidak',
            ]);
        }, $perPage, $page);
    }

    /** @return LengthAwarePaginator<int, OperationalReportRow> */
    private function stockMovements(Warehouse $warehouse, ReportFilters $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $query = StockTransaction::query()
            ->where('warehouse_id', $warehouse->id)
            ->with(['item', 'performer'])
            ->when($filters->itemId, fn (Builder $query, int $itemId) => $query->where('item_id', $itemId))
            ->when($filters->movementType, fn (Builder $query, string $type) => $query->where('movement_type', $type))
            ->when($filters->from, fn (Builder $query, \DateTimeImmutable $from) => $query->where('occurred_at', '>=', $from))
            ->when($filters->to, fn (Builder $query, \DateTimeImmutable $to) => $query->where('occurred_at', '<=', $to))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        return $this->paginate($query, function (StockTransaction $transaction) use ($warehouse): OperationalReportRow {
            $occurredAt = $transaction->occurred_at->timezone($warehouse->timezone)->format('Y-m-d H:i:s');

            return new OperationalReportRow([
                'occurred_at' => $occurredAt,
                'item' => $transaction->item?->name,
                'movement_type' => $transaction->movement_type->value,
                'signed_quantity' => $transaction->signed_quantity,
                'source' => $transaction->source_type,
                'reference' => $transaction->source_id,
                'actor' => $transaction->performer->name,
            ]);
        }, $perPage, $page);
    }

    /** @return LengthAwarePaginator<int, OperationalReportRow> */
    private function purchaseRequests(Warehouse $warehouse, ReportFilters $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $query = PurchaseRequestItem::query()
            ->whereHas('purchaseRequest', function (Builder $query) use ($warehouse, $filters): void {
                $query->where('warehouse_id', $warehouse->id)
                    ->when($filters->status, fn (Builder $query, string $status) => $query->where('status', $status))
                    ->when($filters->source, fn (Builder $query, string $source) => $query->where('source', $source))
                    ->when($filters->from, fn (Builder $query, \DateTimeImmutable $from) => $query->where('created_at', '>=', $from))
                    ->when($filters->to, fn (Builder $query, \DateTimeImmutable $to) => $query->where('created_at', '<=', $to));
            })
            ->with(['purchaseRequest', 'item'])
            ->when($filters->itemId, fn (Builder $query, int $itemId) => $query->where('item_id', $itemId))
            ->orderByDesc('id');

        return $this->paginate($query, function (PurchaseRequestItem $line) use ($warehouse): OperationalReportRow {
            $request = $line->purchaseRequest;
            $terminalAt = match ($request?->status) {
                PurchaseRequestStatus::Rejected => $request->rejected_at,
                PurchaseRequestStatus::Cancelled => $request->cancelled_at,
                PurchaseRequestStatus::Completed => $request->updated_at,
                default => null,
            };

            return new OperationalReportRow([
                'request_number' => $request?->request_number,
                'item' => $line->item?->name,
                'quantity' => $line->requested_quantity,
                'source' => $request?->source?->value,
                'urgency' => $request?->urgency?->value,
                'status' => $request?->status?->value,
                'created_at' => $this->localDate($request?->created_at, $warehouse),
                'terminal_at' => $this->localDate($terminalAt, $warehouse),
            ]);
        }, $perPage, $page);
    }

    /** @return LengthAwarePaginator<int, OperationalReportRow> */
    private function purchaseOrders(Warehouse $warehouse, ReportFilters $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $query = PurchaseOrderItem::query()
            ->whereHas('purchaseOrder', function (Builder $query) use ($warehouse, $filters): void {
                $query->where('warehouse_id', $warehouse->id)
                    ->when($filters->status, fn (Builder $query, string $status) => $query->where('status', $status))
                    ->when($filters->from, fn (Builder $query, \DateTimeImmutable $from) => $query->where('created_at', '>=', $from))
                    ->when($filters->to, fn (Builder $query, \DateTimeImmutable $to) => $query->where('created_at', '<=', $to));
            })
            ->with(['purchaseOrder.supplier', 'item', 'purchaseOrder.goodsReceipt'])
            ->when($filters->itemId, fn (Builder $query, int $itemId) => $query->where('item_id', $itemId))
            ->orderByDesc('id');

        return $this->paginate($query, function (PurchaseOrderItem $line) use ($warehouse): OperationalReportRow {
            $order = $line->purchaseOrder;

            return new OperationalReportRow([
                'po_number' => $order?->po_number,
                'supplier' => $order?->supplier?->name,
                'item' => $line->item?->name,
                'quantity' => $line->ordered_quantity,
                'status' => $order?->status?->value,
                'created_at' => $this->localDate($order?->created_at, $warehouse),
                'sent_at' => $this->localDate($order?->sent_at, $warehouse),
                'received' => $order?->goodsReceipt !== null ? 'Ya' : 'Tidak',
            ]);
        }, $perPage, $page);
    }

    /** @return LengthAwarePaginator<int, OperationalReportRow> */
    private function pickups(Warehouse $warehouse, ReportFilters $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $query = PickupRequestItem::query()
            ->whereHas('pickupRequest', function (Builder $query) use ($warehouse, $filters): void {
                $query->where('warehouse_id', $warehouse->id)
                    ->when($filters->status, fn (Builder $query, string $status) => $query->where('status', $status))
                    ->when($filters->from, fn (Builder $query, \DateTimeImmutable $from) => $query->where('submitted_at', '>=', $from))
                    ->when($filters->to, fn (Builder $query, \DateTimeImmutable $to) => $query->where('submitted_at', '<=', $to));
            })
            ->with(['pickupRequest.user', 'item'])
            ->when($filters->itemId, fn (Builder $query, int $itemId) => $query->where('item_id', $itemId))
            ->orderByDesc('id');

        return $this->paginate($query, function (PickupRequestItem $line) use ($warehouse): OperationalReportRow {
            $pickup = $line->pickupRequest;

            return new OperationalReportRow([
                'request_number' => $pickup?->request_number,
                'koperasi' => $pickup?->user?->name,
                'item' => $line->item?->name,
                'quantity' => $line->requested_quantity,
                'status' => $pickup?->status?->value,
                'requested_at' => $this->localDate($pickup?->submitted_at, $warehouse),
                'scheduled_at' => $this->localDate($pickup?->ready_at, $warehouse),
                'completed_at' => $this->localDate($pickup?->completed_at, $warehouse),
            ]);
        }, $perPage, $page);
    }

    /** @return LengthAwarePaginator<int, OperationalReportRow> */
    private function returns(Warehouse $warehouse, ReportFilters $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $query = ReturnRequestItem::query()
            ->whereHas('returnRequest', function (Builder $query) use ($warehouse, $filters): void {
                $query->where('warehouse_id', $warehouse->id)
                    ->when($filters->status, fn (Builder $query, string $status) => $query->where('status', $status))
                    ->when($filters->from, fn (Builder $query, \DateTimeImmutable $from) => $query->where('submitted_at', '>=', $from))
                    ->when($filters->to, fn (Builder $query, \DateTimeImmutable $to) => $query->where('submitted_at', '<=', $to));
            })
            ->with(['returnRequest.cooperativeMembership.user', 'item'])
            ->when($filters->itemId, fn (Builder $query, int $itemId) => $query->where('item_id', $itemId))
            ->orderByDesc('id');

        return $this->paginate($query, function (ReturnRequestItem $line) use ($warehouse): OperationalReportRow {
            $return = $line->returnRequest;

            return new OperationalReportRow([
                'return_number' => $return?->return_number,
                'koperasi' => $return?->cooperativeMembership?->user?->name,
                'item' => $line->item?->name,
                'quantity' => $line->return_quantity,
                'status' => $return?->status?->value,
                'submitted_at' => $this->localDate($return?->submitted_at, $warehouse),
                'decision_at' => $this->localDate($return->approved_at ?? $return->rejected_at, $warehouse),
                'replacement' => $return?->replacementPickup !== null ? 'Ya' : 'Tidak',
            ]);
        }, $perPage, $page);
    }

    /** @return LengthAwarePaginator<int, OperationalReportRow> */
    private function qualityControl(Warehouse $warehouse, ReportFilters $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $query = QualityInspection::query()
            ->where('warehouse_id', $warehouse->id)
            ->with(['goodsReceiptItem.item', 'goodsReceiptItem.goodsReceipt.purchaseOrder.supplier', 'inspector'])
            ->when($filters->status, fn (Builder $query, string $status) => $query->where('result', $status))
            ->when($filters->itemId, fn (Builder $query, int $itemId) => $query->whereHas('goodsReceiptItem', fn (Builder $query) => $query->where('item_id', $itemId)))
            ->when($filters->from, fn (Builder $query, \DateTimeImmutable $from) => $query->where('inspected_at', '>=', $from))
            ->when($filters->to, fn (Builder $query, \DateTimeImmutable $to) => $query->where('inspected_at', '<=', $to))
            ->orderByDesc('inspected_at')
            ->orderByDesc('id');

        return $this->paginate($query, function (QualityInspection $inspection) use ($warehouse): OperationalReportRow {
            $receiptItem = $inspection->goodsReceiptItem;

            return new OperationalReportRow([
                'receipt_number' => $receiptItem?->goodsReceipt?->receipt_number,
                'po_number' => $receiptItem?->goodsReceipt?->purchaseOrder?->po_number,
                'supplier' => $receiptItem?->goodsReceipt?->purchaseOrder?->supplier?->name,
                'item' => $receiptItem?->item?->name,
                'result' => $inspection->result->value,
                'inspector' => $inspection->inspector?->name,
                'inspected_at' => $this->localDate($inspection->inspected_at, $warehouse),
            ]);
        }, $perPage, $page);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  callable(TModel): OperationalReportRow  $map
     * @return LengthAwarePaginator<int, OperationalReportRow>
     */
    private function paginate(Builder $query, callable $map, int $perPage, int $page): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, TModel> $paginator */
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return new LengthAwarePaginator(
            $paginator->getCollection()->map($map),
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            ['path' => $paginator->path(), 'pageName' => $paginator->getPageName()],
        );
    }

    private function localDate(?DateTimeInterface $date, Warehouse $warehouse): ?string
    {
        return $date === null ? null : CarbonImmutable::instance($date)->timezone($warehouse->timezone)->format('Y-m-d H:i:s');
    }
}
