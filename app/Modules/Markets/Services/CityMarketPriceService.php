<?php

namespace App\Modules\Markets\Services;

use App\Models\DailyMarketPrice;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;



class CityMarketPriceService
{

    public function listWithSummary(array $filters)
    {
        /**
         * =========================================================
         * BASE LIST QUERY (FILTER + PAGINATION)
         * =========================================================
         */
        $query = DailyMarketPrice::query()
            ->with(['item', 'city'])

            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->whereHas('item', function ($item) use ($search) {
                        $item->where('name', 'like', "%{$search}%");
                    });

                    $query->orWhereHas('city', function ($city) use ($search) {
                        $city->where('name', 'like', "%{$search}%");
                    });
                });
            })
            ->orderByDesc('price_date');
    
        $paginator = $query->paginate($filters['per_page'] ?? 20);
    
        /**
         * =========================================================
         * SMART SUMMARY (LATEST + PREVIOUS PER ITEM)
         * =========================================================
         */
    
        $cityId = $filters['city_id'] ?? null;
        $priceType = $filters['price_type'] ?? null;
    
        /**
         * ---------------------------------------------------------
         * LATEST PRICE PER ITEM
         * ---------------------------------------------------------
         */
        $latest = DailyMarketPrice::query()
            ->select('market_item_id', 'price', 'price_date')
            ->when($cityId, fn($q) => $q->where('city_id', $cityId))
            ->when($priceType, fn($q) => $q->where('price_type', $priceType))
            ->whereIn('price_date', function ($sub) use ($cityId, $priceType) {
                $sub->selectRaw('MAX(price_date)')
                    ->from('daily_market_prices')
                    ->when($cityId, fn($q) => $q->where('city_id', $cityId))
                    ->when($priceType, fn($q) => $q->where('price_type', $priceType))
                    ->groupBy('market_item_id');
            })
            ->get()
            ->keyBy('market_item_id');
    
        /**
         * ---------------------------------------------------------
         * PREVIOUS PRICE PER ITEM
         * ---------------------------------------------------------
         */
        $previous = DailyMarketPrice::query()
            ->select('market_item_id', 'price')
            ->when($cityId, fn($q) => $q->where('city_id', $cityId))
            ->when($priceType, fn($q) => $q->where('price_type', $priceType))
            ->whereIn('price_date', function ($sub) use ($cityId, $priceType) {
                $sub->selectRaw('MAX(price_date)')
                    ->from('daily_market_prices')
                    ->when($cityId, fn($q) => $q->where('city_id', $cityId))
                    ->when($priceType, fn($q) => $q->where('price_type', $priceType))
                    ->whereRaw('price_date < CURRENT_DATE')
                    ->groupBy('market_item_id');
            })
            ->get()
            ->keyBy('market_item_id');
    
        /**
         * =========================================================
         * TREND CALCULATION
         * =========================================================
         */
        $rising = 0;
        $falling = 0;
        $stable = 0;
    
        foreach ($latest as $itemId => $current) {
            $prev = $previous[$itemId] ?? null;
    
            if (!$prev) {
                continue;
            }
    
            if ($current->price > $prev->price) {
                $rising++;
            } elseif ($current->price < $prev->price) {
                $falling++;
            } else {
                $stable++;
            }
        }
    
        /**
         * =========================================================
         * RESPONSE
         * =========================================================
         */
        return [
            'items' => $paginator,
    
            'summary' => [
                'total_items' => $paginator->total(),
                'rising' => $rising,
                'falling' => $falling,
                'stable' => $stable,
            ],
        ];
    }


    /**
     * =========================================================
     * CREATE (PRIMARY WRITE METHOD - UPSERT MODEL)
     * =========================================================
     *
     * NOTE:
     * Daily snapshot system → create = upsert
     */
    public function create(array $data, string $userId)
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception("Unauthenticated user.");
        }

        // Inject city_id from logged-in user
        $data['city_id'] = $user->city_id;
        $data['created_by'] = $userId;

        return $this->upsert($data, $userId);
    }

    /**
     * =========================================================
     * FIND SINGLE RECORD
     * =========================================================
     */
    public function find(string $id)
    {
        return DailyMarketPrice::findOrFail($id);
    }

    /**
     * =========================================================
     * UPDATE (ADMIN / CORRECTION PURPOSE ONLY)
     * =========================================================
     */
    public function update(string $id, array $data, string $userId)
    {
        $record = DailyMarketPrice::findOrFail($id);

        $record->update([
            'price' => $data['price'] ?? $record->price,
            'currency' => $data['currency'] ?? $record->currency,
            'price_type' => $data['price_type'] ?? $record->price_type,
            'source' => $data['source'] ?? $record->source,
            'confidence_score' => $data['confidence_score'] ?? $record->confidence_score,
        ]);

        return $record;
    }

    /**
     * =========================================================
     * LATEST PRICE
     * =========================================================
     */
    public function latest(array $filters)
    {
        return DailyCityMarketPrice::query()
            ->where('city_id', $filters['city_id'])
            ->where('market_item_id', $filters['market_item_id'])
            ->when($filters['price_type'] ?? null, fn($q, $v) =>
                $q->where('price_type', $v)
            )
            ->orderByDesc('price_date')
            ->first();
    }

    /**
     * =========================================================
     * SUMMARY ANALYTICS
     * =========================================================
     */
    public function summary(array $filters)
    {
        $baseQuery = DailyCityMarketPrice::query()
            ->where('city_id', $filters['city_id'])
            ->where('market_item_id', $filters['market_item_id'])
            ->when($filters['price_type'] ?? null, fn($q, $v) =>
                $q->where('price_type', $v)
            );

        $latest = (clone $baseQuery)
            ->orderByDesc('price_date')
            ->first();

        return [
            'avg_price' => (clone $baseQuery)->avg('price'),
            'min_price' => (clone $baseQuery)->min('price'),
            'max_price' => (clone $baseQuery)->max('price'),

            'latest_price' => $latest?->price,
            'latest_date' => $latest?->price_date,

            'total_records' => (clone $baseQuery)->count(),
        ];
    }

    /**
     * =========================================================
     * UPSERT (CORE LOGIC OF DAILY SNAPSHOT SYSTEM)
     * =========================================================
     */
    public function upsert(array $data, string $userId)
    {
        // -----------------------------------------------------
        // FORCE SERVER-SIDE DATE (NO TRUST FROM FRONTEND)
        // -----------------------------------------------------
        $priceDate = Carbon::today()->toDateString();
    
        return DailyMarketPrice::updateOrCreate(
            [
                'city_id' => $data['city_id'],
                'market_item_id' => $data['market_item_id'],
                'price_date' => $priceDate, // ALWAYS SERVER CONTROLLED
                'price_type' => $data['price_type'] ?? 'official',
            ],
            [
                'price' => $data['price'],
                'currency' => $data['currency'] ?? 'ETB',
                'source' => $data['source'] ?? 'manual',
                'confidence_score' => $data['confidence_score'] ?? null,
                'created_by' => $userId,
            ]
        );
    }

    public function delete(DailyMarketPrice $item): void
    {
        $item->delete();
    }
}