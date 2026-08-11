<?php

namespace App\Enums;

enum ReturnEvidencePurpose: string
{
    case ReturnSubmission = 'RETURN_SUBMISSION';
    case ReturnVerification = 'RETURN_VERIFICATION';
}
