<?php

namespace App\Enums;

/**
 * Tenant-scoped roles attached to a WarehouseMembership.
 *
 * `app_admin` is the only role that can be provisioned via manual registration;
 * every other role is created internally by an `app_admin` (invitation-only).
 */
enum WarehouseRole: string
{
    case AppAdmin = 'app_admin';
    case KepalaGudang = 'kepala_gudang';
    case StaffAdmin = 'staff_admin';
    case Purchasing = 'purchasing';
    case Koperasi = 'koperasi';

    public function label(): string
    {
        return match ($this) {
            self::AppAdmin => 'App Admin',
            self::KepalaGudang => 'Kepala Gudang',
            self::StaffAdmin => 'Staff Admin',
            self::Purchasing => 'Purchasing',
            self::Koperasi => 'Koperasi',
        };
    }

    /**
     * Roles an `app_admin` is allowed to assign to internal users.
     *
     * @return list<self>
     */
    public static function internal(): array
    {
        return [self::KepalaGudang, self::StaffAdmin, self::Purchasing, self::Koperasi];
    }

    /**
     * Default ACL granted to this role. Membership `permissions` may narrow
     * this set further but never widen it beyond what the role template allows.
     *
     * @return list<Permission>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::AppAdmin => Permission::cases(),
            self::KepalaGudang => [
                Permission::DashboardView,
                Permission::StockView,
                Permission::StockMinimumManage,
                Permission::PurchaseRequestApprove,
                Permission::PurchaseRequestCancel,
                Permission::ExpenseApprove,
                Permission::ReturnApprove,
                Permission::PredictionRun,
                Permission::ReportsView,
                Permission::ItemViewAny,
                Permission::LocationViewAny,
                Permission::SupplierViewAny,
                Permission::StockLedgerView,
            ],
            self::StaffAdmin => [
                Permission::DashboardView,
                Permission::StockView,
                Permission::StockManage,
                Permission::StockMinimumManage,
                Permission::PurchaseRequestCreate,
                Permission::ReceivingQcManage,
                Permission::ReturnVerify,
                Permission::ExpenseRecord,
                Permission::ItemViewAny,
                Permission::ItemCreate,
                Permission::ItemUpdate,
                Permission::ItemArchive,
                Permission::LocationViewAny,
                Permission::LocationManage,
                Permission::SupplierViewAny,
                Permission::SupplierManage,
                Permission::StockAdjust,
                Permission::StockScanIn,
                Permission::StockScanOut,
                Permission::StockLedgerView,
            ],
            self::Purchasing => [
                Permission::DashboardView,
                Permission::PurchaseOrderCreate,
                Permission::PurchaseOrderManage,
                Permission::ReceivingQcManage,
                Permission::ReportsView,
                Permission::ItemViewAny,
                Permission::SupplierViewAny,
                Permission::SupplierManage,
                Permission::StockLedgerView,
            ],
            self::Koperasi => [
                Permission::KoperasiRequestCreate,
                Permission::KoperasiReturnSubmit,
            ],
        };
    }
}
