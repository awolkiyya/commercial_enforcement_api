<?php
namespace App\Enums;


enum LicenseStatus: string
{
    case VALID = 'valid';
    case EXPIRED = 'expired';
    case MISSING = 'missing';
    case UNDER_REVIEW = 'under_review';
}