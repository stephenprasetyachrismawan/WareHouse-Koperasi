<?php

namespace App\Enums;

/**
 * Access control options that gate features and sub-features.
 *
 * These are not yet tied to concrete models/policies — they are the ACL
 * vocabulary used to restrict feature visibility per role/membership until
 * per-model policies land.
 */
enum Permission: string
{
    case UsersManage = 'users.manage';
    case RolesManage = 'roles.manage';
    case CompanySettingsManage = 'company_settings.manage';
    case DashboardView = 'dashboard.view';

    case StockView = 'stock.view';
    case StockManage = 'stock.manage';
    case StockMinimumManage = 'stock.minimum.manage';

    case PurchaseRequestViewAny = 'purchase_request.viewAny';
    case PurchaseRequestView = 'purchase_request.view';
    case PurchaseRequestCreate = 'purchase_request.create';
    case PurchaseRequestApprove = 'purchase_request.approve';
    case PurchaseRequestReject = 'purchase_request.reject';
    case PurchaseRequestCancel = 'purchase_request.cancel';
    case PurchaseRequestRequestCancellation = 'purchase_request.requestCancellation';

    case PurchaseOrderViewAny = 'purchase_order.viewAny';
    case PurchaseOrderView = 'purchase_order.view';
    case PurchaseOrderCreate = 'purchase_order.create';
    case PurchaseOrderSend = 'purchase_order.send';
    case PurchaseOrderManage = 'purchase_order.manage';

    case PurchaseGroupViewAny = 'purchase_group.viewAny';
    case PurchaseGroupCreate = 'purchase_group.create';
    case PurchaseGroupUpdate = 'purchase_group.update';

    case ReceiptViewAny = 'receipts.viewAny';
    case ReceiptView = 'receipts.view';
    case ReceiptCreate = 'receipts.create';
    case ReceiptQc = 'receipts.qc';

    case ExpenseRecord = 'expense.record';
    case ExpenseApprove = 'expense.approve';

    case ReturnVerify = 'return.verify';
    case ReturnApprove = 'return.approve';

    case PredictionRun = 'prediction.run';
    case ReportsView = 'reports.view';

    case ItemViewAny = 'item.viewAny';
    case ItemCreate = 'item.create';
    case ItemUpdate = 'item.update';
    case ItemArchive = 'item.archive';
    case LocationViewAny = 'location.viewAny';
    case LocationManage = 'location.manage';
    case SupplierViewAny = 'supplier.viewAny';
    case SupplierManage = 'supplier.manage';
    case StockAdjust = 'stock.adjust';
    case StockScanIn = 'stock.scanIn';
    case StockScanOut = 'stock.scanOut';
    case StockLedgerView = 'stock.ledger.view';

    case KoperasiRequestCreate = 'koperasi.request.create';
    case KoperasiReturnSubmit = 'koperasi.return.submit';

    case PickupRequestViewAny = 'pickup_request.viewAny';
    case PickupRequestCreate = 'pickup_request.create';
    case PickupRequestPrepare = 'pickup_request.prepare';
    case PickupRequestApprove = 'pickup_request.approve';
    case PickupRequestFulfill = 'pickup_request.fulfill';
    case PickupRequestCancel = 'pickup_request.cancel';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $permission) => $permission->value, self::cases());
    }
}
