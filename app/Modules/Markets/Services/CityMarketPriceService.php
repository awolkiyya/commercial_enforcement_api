<?php

namespace App\Modules\Markets\Services;

use App\Models\DailyMarketPrice;
use App\Models\MarketItem;
use App\Models\City;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CityMarketPriceService
{
    /**
     * =========================================================
     * LIST WITH SUMMARY (FIXED — NO DUPLICATE ROWS)
     * =========================================================
     *
     * Previously this returned every daily snapshot row as if it
     * were a separate "current price" item. That's why the same
     * item showed up 4 times for 4 different price_date rows.
     *
     * Now: one row per (market_item_id, city_id). Latest snapshot
     * = current price, the one before it = previous price, and
     * the last N snapshots feed the `history` array.
     */
    public function listWithSummary(array $filters)
    {
        $search       = $filters['search'] ?? null;
        $cityId       = $filters['city_id'] ?? null;
        $priceType    = $filters['price_type'] ?? null;
        $historyLimit = $filters['history_limit'] ?? 10;

        /**
         * =========================================================
         * STEP 1: DISTINCT (item, city) GROUPS — THIS REPLACES THE
         * OLD RAW ->paginate() OVER daily_market_prices, WHICH IS
         * WHY THE SAME ITEM USED TO SHOW UP MULTIPLE TIMES.
         * =========================================================
         */
        $groupsQuery = DailyMarketPrice::query()
            ->select('market_item_id', 'city_id', DB::raw('MAX(price_date) as latest_date'))
            ->when($cityId, fn($q) => $q->where('city_id', $cityId))
            ->when($priceType, fn($q) => $q->where('price_type', $priceType))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->whereHas('item', function ($item) use ($search) {
                        $item->where('name', 'like', "%{$search}%");
                    });

                    $query->orWhereHas('city', function ($city) use ($search) {
                        $city->where('name', 'like', "%{$search}%");
                    });
                });
            })
            ->groupBy('market_item_id', 'city_id')
            ->orderByDesc('latest_date');

        $paginator = $groupsQuery->paginate($filters['per_page'] ?? 20)->appends($filters);

        $pairs = $paginator->getCollection();

        /**
         * ---------------------------------------------------------
         * Bulk-load related items/cities to avoid N+1 queries
         * ---------------------------------------------------------
         */
        $itemIds = $pairs->pluck('market_item_id')->unique();
        $cityIds = $pairs->pluck('city_id')->unique();

        $items  = MarketItem::whereIn('id', $itemIds)->get()->keyBy('id');
        $cities = City::whereIn('id', $cityIds)->get()->keyBy('id');

        /**
         * =========================================================
         * SMART SUMMARY (LATEST + PREVIOUS PER ITEM) — SAME LOGIC
         * AS BEFORE, NOW ALSO KEYED BY city_id SO DIFFERENT CITIES
         * DON'T COLLIDE ON THE SAME market_item_id
         * =========================================================
         */

        /**
         * ---------------------------------------------------------
         * LATEST PRICE PER ITEM
         * ---------------------------------------------------------
         */
        $latest = DailyMarketPrice::query()
            ->select('market_item_id', 'city_id', 'price', 'price_date')
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
            ->keyBy(fn($row) => $row->market_item_id . ':' . $row->city_id);

        /**
         * ---------------------------------------------------------
         * PREVIOUS PRICE PER ITEM
         * ---------------------------------------------------------
         */
        $previous = DailyMarketPrice::query()
            ->select('market_item_id', 'city_id', 'price')
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
            ->keyBy(fn($row) => $row->market_item_id . ':' . $row->city_id);

        /**
         * =========================================================
         * TREND CALCULATION (GLOBAL, ACROSS ALL MATCHING GROUPS —
         * NOT JUST THE CURRENT PAGE)
         * =========================================================
         */
        $rising = 0;
        $falling = 0;
        $stable = 0;

        foreach ($latest as $key => $current) {
            $prev = $previous[$key] ?? null;

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
         * BUILD THE ACTUAL ITEMS LIST (ONE ROW PER item+city, WITH
         * currentPrice / previousPrice / trend / history ATTACHED)
         * =========================================================
         */
        $mapped = $pairs->map(function ($pair) use ($items, $cities, $priceType, $historyLimit, $latest, $previous) {
            $key = $pair->market_item_id . ':' . $pair->city_id;

            $current = $latest[$key] ?? null;
            $prev    = $previous[$key] ?? null;

            $currentPrice  = $current->price ?? 0;
            $previousPrice = $prev->price ?? 0;

            $changePercent = $previousPrice > 0
                ? round((($currentPrice - $previousPrice) / $previousPrice) * 100, 2)
                : 0;

            $trend = 'STABLE';
            if ($prev) {
                $trend = $currentPrice > $previousPrice
                    ? 'UP'
                    : ($currentPrice < $previousPrice ? 'DOWN' : 'STABLE');
            }

            $historyRows = DailyMarketPrice::query()
                ->where('market_item_id', $pair->market_item_id)
                ->where('city_id', $pair->city_id)
                ->when($priceType, fn($q) => $q->where('price_type', $priceType))
                ->orderByDesc('price_date')
                ->limit($historyLimit)
                ->pluck('price')
                ->reverse()
                ->values();

            $item = $items->get($pair->market_item_id);
            $city = $cities->get($pair->city_id);

            return [
                'id'            => $pair->market_item_id . ':' . $pair->city_id,
                'item'          => [
                    'id'   => $item->id ?? null,
                    'name' => $item->name ?? null,
                    'unit' => $item->unit ?? null,
                ],
                'cityId'        => $pair->city_id,
                'cityName'      => $city->name ?? null,
                'unit'          => $item->unit ?? null,
                'currentPrice'  => $currentPrice,
                'previousPrice' => $previousPrice,
                'changePercent' => $changePercent,
                'trend'         => $trend,
                'history'       => $historyRows,
            ];
        });

        /**
         * =========================================================
         * RESPONSE
         * =========================================================
         */
        return [
            'items' => $paginator->setCollection($mapped),

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
     * Daily snapshot system → create = upsert.
     * If a record already exists for (city, item, date, price_type),
     * it gets UPDATED instead of duplicated. This part was already
     * correct in your original code.
     */
    public function create(array $data, string $userId)
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception("Unauthenticated user.");
        }

        // Inject city_id from logged-in user
        $data['city_id']   = $user->city_id;
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
            'price'            => $data['price'] ?? $record->price,
            'currency'         => $data['currency'] ?? $record->currency,
            'price_type'       => $data['price_type'] ?? $record->price_type,
            'source'           => $data['source'] ?? $record->source,
            'confidence_score' => $data['confidence_score'] ?? $record->confidence_score,
        ]);

        return $record;
    }

    /**
     * =========================================================
     * LATEST PRICE
     * =========================================================
     * FIX: was referencing an undefined class `DailyCityMarketPrice`.
     * Corrected to `DailyMarketPrice`.
     */
    public function latest(array $filters)
    {
        return DailyMarketPrice::query()
            ->where('city_id', $filters['city_id'])
            ->where('market_item_id', $filters['market_item_id'])
            ->when($filters['price_type'] ?? null, fn ($q, $v) => $q->where('price_type', $v))
            ->orderByDesc('price_date')
            ->first();
    }

    /**
     * =========================================================
     * SUMMARY ANALYTICS
     * =========================================================
     * FIX: was referencing an undefined class `DailyCityMarketPrice`.
     * Corrected to `DailyMarketPrice`.
     */
    public function summary(array $filters)
    {
        $baseQuery = DailyMarketPrice::query()
            ->where('city_id', $filters['city_id'])
            ->where('market_item_id', $filters['market_item_id'])
            ->when($filters['price_type'] ?? null, fn ($q, $v) => $q->where('price_type', $v));

        $latest = (clone $baseQuery)
            ->orderByDesc('price_date')
            ->first();

        return [
            'avg_price' => (clone $baseQuery)->avg('price'),
            'min_price' => (clone $baseQuery)->min('price'),
            'max_price' => (clone $baseQuery)->max('price'),

            'latest_price' => $latest?->price,
            'latest_date'  => $latest?->price_date,

            'total_records' => (clone $baseQuery)->count(),
        ];
    }

    /**
     * =========================================================
     * UPSERT (CORE LOGIC OF DAILY SNAPSHOT SYSTEM)
     * =========================================================
     * Already correct — kept as-is. This is what prevents
     * duplicate rows for the SAME day. Duplicates you saw in
     * the list came from different days being shown as separate
     * "current price" entries (fixed in listWithSummary above).
     */
    public function upsert(array $data, string $userId)
    {
        // -----------------------------------------------------
        // FORCE SERVER-SIDE DATE (NO TRUST FROM FRONTEND)
        // -----------------------------------------------------
        $priceDate = Carbon::today()->toDateString();

        return DailyMarketPrice::updateOrCreate(
            [
                'city_id'        => $data['city_id'],
                'market_item_id' => $data['market_item_id'],
                'price_date'     => $priceDate, // ALWAYS SERVER CONTROLLED
                'price_type'     => $data['price_type'] ?? 'official',
            ],
            [
                'price'            => $data['price'],
                'currency'         => $data['currency'] ?? 'ETB',
                'source'           => $data['source'] ?? 'manual',
                'confidence_score' => $data['confidence_score'] ?? null,
                'created_by'       => $userId,
            ]
        );
    }

    public function delete(DailyMarketPrice $item): void
    {
        $item->delete();
    }
}