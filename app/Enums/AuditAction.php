<?php

namespace App\Enums;


enum AuditAction: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case LOGIN = 'login';
    case APPROVE = 'approve';
    case REJECT = 'reject';
}