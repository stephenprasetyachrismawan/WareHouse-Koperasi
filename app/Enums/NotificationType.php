<?php

namespace App\Enums;

/**
 * Stable, durable business identifier persisted alongside every inbox
 * notification. Never rely on a PHP class name (Notification/Listener) as
 * the durable meaning of a record — those may be refactored later, but this
 * value must remain stable so historical inbox rows stay understandable.
 */
enum NotificationType: string
{
    case ApprovalRequired = 'APPROVAL_REQUIRED';
    case ApprovalApproved = 'APPROVAL_APPROVED';
    case ApprovalRejected = 'APPROVAL_REJECTED';

    case DuplicatePurchaseWarning = 'DUPLICATE_PURCHASE_WARNING';
    case PurchaseRequestStatus = 'PURCHASE_REQUEST_STATUS';

    case CancellationRequired = 'CANCELLATION_REQUIRED';
    case CancellationStatus = 'CANCELLATION_STATUS';

    case PoStatus = 'PO_STATUS';
    case ReceiptRequired = 'RECEIPT_REQUIRED';

    case PickupRequested = 'PICKUP_REQUESTED';
    case ReadyForPickup = 'READY_FOR_PICKUP';

    case ReturnSubmitted = 'RETURN_SUBMITTED';
    case ReturnStatus = 'RETURN_STATUS';
    case ReplacementReady = 'REPLACEMENT_READY';

    case SecurityAlert = 'SECURITY_ALERT';
    case Invitation = 'INVITATION';

    public function label(): string
    {
        return match ($this) {
            self::ApprovalRequired => 'Persetujuan Diperlukan',
            self::ApprovalApproved => 'Disetujui',
            self::ApprovalRejected => 'Ditolak',
            self::DuplicatePurchaseWarning => 'Peringatan Duplikat',
            self::PurchaseRequestStatus => 'Status Purchase Request',
            self::CancellationRequired => 'Pembatalan Perlu Keputusan',
            self::CancellationStatus => 'Status Pembatalan',
            self::PoStatus => 'Status Purchase Order',
            self::ReceiptRequired => 'Penerimaan Barang Diperlukan',
            self::PickupRequested => 'Permintaan Pengambilan Baru',
            self::ReadyForPickup => 'Siap Diambil',
            self::ReturnSubmitted => 'Retur Diajukan',
            self::ReturnStatus => 'Status Retur',
            self::ReplacementReady => 'Penggantian Siap',
            self::SecurityAlert => 'Peringatan Keamanan',
            self::Invitation => 'Undangan',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ApprovalRequired, self::CancellationRequired => 'clock',
            self::ApprovalApproved, self::ReadyForPickup, self::ReplacementReady => 'check-circle',
            self::ApprovalRejected => 'x-circle',
            self::DuplicatePurchaseWarning, self::SecurityAlert => 'exclamation-triangle',
            self::PurchaseRequestStatus, self::CancellationStatus, self::ReturnStatus, self::PoStatus => 'information-circle',
            self::ReceiptRequired => 'truck',
            self::PickupRequested => 'shopping-cart',
            self::ReturnSubmitted => 'arrow-uturn-left',
            self::Invitation => 'envelope',
        };
    }
}
