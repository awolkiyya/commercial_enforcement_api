<?php

namespace App\Enums;


enum InspectionType: string
{
    case ROUTINE = 'routine';
    case COMPLAINT = 'complaint';
    case RANDOM = 'random';
    case EMERGENCY = 'emergency';
}