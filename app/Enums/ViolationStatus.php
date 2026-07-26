<?php

namespace App\Enums;

enum ViolationStatus: string
{
    case DETECTED = 'detected';
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PENALIZED = 'penalized';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
}