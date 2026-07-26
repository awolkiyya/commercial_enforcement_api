<?php
namespace App\Enums;


enum PenaltyAction: string
{
    case WARNING = 'warning';
    case FINE = 'fine';
    case TEMPORARY_CLOSURE = 'temporary_closure';
    case PERMANENT_CLOSURE = 'permanent_closure';
}