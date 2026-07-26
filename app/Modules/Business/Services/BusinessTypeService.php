<?php

namespace App\Modules\Business\Services;

use App\Models\BusinessType;

class BusinessTypeService
{
    public function getAll()
    {
        return BusinessType::query()
            ->where('is_active', true)
            ->orderBy('priority_level', 'desc')
            ->get();
    }
}