<?php
namespace App\Enums;

enum BusinessComplianceStatus: string
{
    case FULLY_COMPLIANT = 'fully_compliant'; // has license + tin
    case PARTIALLY_COMPLIANT = 'partial';     // missing one
    case UNREGISTERED = 'unregistered';       // no documents
}