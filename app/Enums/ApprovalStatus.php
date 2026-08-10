<?php

namespace App\Enums;

enum ApprovalStatus: string
{
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case AutoApproved = 'AUTO_APPROVED';
}
