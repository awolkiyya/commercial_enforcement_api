<?php
namespace App\Enums;


enum BusinessStatus: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case CLOSED = 'closed';
    case UNDER_INSPECTION = 'under_inspection';
}