<?php

namespace  App\Modules\Markets\Services;

use App\Models\MarketItem;

class MarketItemService
{
    public function list(array $filters = [])
    {
        $query = MarketItem::query()->with('category');
    
        $perPage = $filters['per_page'] ?? 20;
    
        // ----------------------------
        // SEARCH
        // ----------------------------
        if (!empty($filters['search'])) {
            $search = $filters['search'];
    
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('unit', 'LIKE', "%{$search}%");
            });
        }
    
        // ----------------------------
        // CATEGORY FILTER
        // ----------------------------
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
    
        // ----------------------------
        // STATUS FILTER
        // ----------------------------
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('is_active', $filters['status'] === 'active');
        }
    
        // ----------------------------
        // PAGINATION
        // ----------------------------
        return $query
            ->latest()
            ->paginate($perPage, ['*'], 'page', $filters['page'] ?? 1)
            ->appends($filters);
    }

    public function create(array $data)
    {
        return MarketItem::create($data);
    }

    public function update(MarketItem $item, array $data)
    {
        $item->update($data);
        return $item;
    }

    public function find(string $id)
    {
        return MarketItem::findOrFail($id);
    }
    public function delete(MarketItem $item): void
    {
        $item->delete();
    }
}