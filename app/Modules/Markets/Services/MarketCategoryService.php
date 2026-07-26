<?php

namespace App\Modules\Markets\Services;

use App\Models\MarketCategory;

class MarketCategoryService
{
    public function list()
    {
        return MarketCategory::query()->latest()->paginate(20);
    }

    public function create(array $data)
    {
        return MarketCategory::create($data);
    }

    public function update(MarketCategory $category, array $data)
    {
        $category->update($data);
        return $category;
    }

    public function find(string $id)
    {
        return MarketCategory::findOrFail($id);
    }

    public function delete(MarketCategory $category): void
{
    $category->delete();
}
}