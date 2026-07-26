<?php

namespace App\Modules\Markets\Services;

use App\Models\DailyMarketPrice;
use App\Models\MarketItem;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Throwable;

class MarketPriceHistoryService
{
    /**
     * =========================================================
     * MAIN ENTRY
     * =========================================================
     */
    public function getHistory(array $params): object
    {
        $marketItemId = $params['market_item_id'];

        [$from, $to] = $this->resolveDateRange($params);

        /**
         * =========================================================
         * BASE QUERY (SAFE + CONSISTENT)
         * =========================================================
         */
        $query = DailyMarketPrice::query()
            ->where('market_item_id', $marketItemId)
            ->when($from, fn ($q) => $q->whereDate('price_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('price_date', '<=', $to))
            ->orderBy('price_date', 'asc');

        $prices = $query->get();

        /**
         * =========================================================
         * ITEM (ALWAYS RELIABLE)
         * =========================================================
         */
        $item = MarketItem::query()->find($marketItemId);

        /**
         * =========================================================
         * EMPTY SAFETY
         * =========================================================
         */
        if ($prices->isEmpty()) {
            return (object)[
                'points' => [],
                'summary' => [
                    'minPrice' => 0,
                    'maxPrice' => 0,
                    'averagePrice' => 0,
                    'count' => 0,
                ],
                'trend' => 'STABLE',
                'item' => $item,
            ];
        }

        /**
         * =========================================================
         * POINTS (CHART DATA)
         * =========================================================
         */
        $points = $prices->map(fn ($p) => [
            'date'  => Carbon::parse($p->price_date)->format('Y-m-d'),
            'price' => (float) $p->current_price,
        ])->values();

        /**
         * =========================================================
         * SUMMARY
         * =========================================================
         */
        $summary = [
            'minPrice'     => (float) $prices->min('current_price'),
            'maxPrice'     => (float) $prices->max('current_price'),
            'averagePrice' => round((float) $prices->avg('current_price'), 2),
            'count'        => $prices->count(),
        ];

        /**
         * =========================================================
         * TREND (ROBUST)
         * =========================================================
         */
        $trend = $this->calculateTrend($prices);

        /**
         * =========================================================
         * RESPONSE DTO
         * =========================================================
         */
        return (object)[
            'points'  => $points,
            'summary' => $summary,
            'trend'   => $trend,
            'item'    => $item,
        ];
    }

    /**
     * =========================================================
     * RANGE RESOLUTION (SAFE + DEFAULTED)
     * =========================================================
     */
    private function resolveDateRange(array $params): array
    {
        $range = $params['range'] ?? '30d';

        try {
            $to = !empty($params['to'])
                ? Carbon::parse($params['to'])
                : now();

            $from = match ($range) {
                '7d' => now()->subDays(7),
                '30d' => now()->subDays(30),
                '90d' => now()->subDays(90),
                '1y'  => now()->subYear(),
                'custom' => !empty($params['from'])
                    ? Carbon::parse($params['from'])
                    : now()->subDays(30),
                default => now()->subDays(30),
            };

            return [
                $from->toDateString(),
                $to->toDateString(),
            ];
        } catch (Throwable $e) {
            // fallback safe range
            return [
                now()->subDays(30)->toDateString(),
                now()->toDateString(),
            ];
        }
    }

    /**
     * =========================================================
     * TREND CALCULATION (STABLE + NOISE-RESISTANT)
     * =========================================================
     */
    private function calculateTrend(Collection $prices): string
    {
        if ($prices->count() < 2) {
            return 'STABLE';
        }

        $values = $prices->pluck('current_price')->values();

        $first = (float) $values->first();
        $last  = (float) $values->last();

        if ($first <= 0) {
            return 'STABLE';
        }

        $diffPercent = (($last - $first) / $first) * 100;

        if ($diffPercent > 1.5) {
            return 'UP';
        }

        if ($diffPercent < -1.5) {
            return 'DOWN';
        }

        return 'STABLE';
    }
}