<?php
namespace App\Enums;


enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case CITY_ADMIN = 'city_admin';
    case SUBCITY_ADMIN = 'subcity_admin';
    case WEREDA_ADMIN = 'wereda_admin';
    case INSPECTOR = 'inspector';
}