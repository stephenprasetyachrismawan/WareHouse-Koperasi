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

    case PurchaseRequestCreate = 'purchase_request.create';
    case PurchaseRequestApprove = 'purchase_request.approve';
    case PurchaseRequestCancel = 'purchase_request.cancel';

    case PurchaseOrderCreate = 'purchase_order.create';
    case PurchaseOrderManage = 'purchase_order.manage';

    case ReceivingQcManage = 'receiving_qc.manage';

    case ExpenseRecord = 'expense.record';
    case ExpenseApprove = 'expense.approve';

    case ReturnVerify = 'return.verify';
    case ReturnApprove = 'return.approve';

    case PredictionRun = 'prediction.run';
    case ReportsView = 'reports.view';

    case KoperasiRequestCreate = 'koperasi.request.create';
    case KoperasiReturnSubmit = 'koperasi.return.submit';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $permission) => $permission->value, self::cases());
    }
}
