<?php

declare(strict_types=1);

namespace App\Enums;

enum OperationalReportType: string
{
    case Stock = 'stock';
    case StockMovements = 'stock_movements';
    case PurchaseRequests = 'purchase_requests';
    case PurchaseOrders = 'purchase_orders';
    case Pickups = 'pickups';
    case Returns = 'returns';
    case QualityControl = 'quality_control';

    public function label(): string
    {
        return match ($this) {
            self::Stock => 'Stok',
            self::StockMovements => 'Mutasi Stok',
            self::PurchaseRequests => 'Purchase Request',
            self::PurchaseOrders => 'Purchase Order',
            self::Pickups => 'Pickup',
            self::Returns => 'Return',
            self::QualityControl => 'Penerimaan & QC',
        };
    }
}
