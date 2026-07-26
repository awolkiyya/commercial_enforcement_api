<?php

namespace App\Modules\Markets\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\DailyMarketPrice;

class CityMarketPriceResource extends JsonResource
{
    public function toArray($request): array
    {
        $itemId = $this->market_item_id;
        $cityId = $this->city_id;

        /**
         * =========================================================
         * CURRENT PRICE
         * =========================================================
         */
        $currentPrice = (float) $this->price;

        /**
         * =========================================================
         * PREVIOUS PRICE
         * =========================================================
         */
        $previousPrice = DailyMarketPrice::query()
            ->where('city_id', $cityId)
            ->where('market_item_id', $itemId)
            ->where('price_type', $this->price_type)
            ->where('price_date', '<', $this->price_date)
            ->orderByDesc('price_date')
            ->value('price');

        $previousPrice = $previousPrice ? (float) $previousPrice : 0;

        /**
         * =========================================================
         * CHANGE %
         * =========================================================
         */
        $changePercent = 0;

        if ($previousPrice > 0) {
            $changePercent = (($currentPrice - $previousPrice) / $previousPrice) * 100;
        }

        /**
         * =========================================================
         * HISTORY (last 7 values)
         * =========================================================
         */
        $history = DailyMarketPrice::query()
            ->where('city_id', $cityId)
            ->where('market_item_id', $itemId)
            ->where('price_type', $this->price_type)
            ->orderByDesc('price_date')
            ->limit(7)
            ->pluck('price')
            ->reverse()
            ->map(fn ($price) => (float) $price)
            ->values()
            ->toArray();

        /**
         * =========================================================
         * TREND TYPE
         * =========================================================
         */
        $trend = "STABLE";

        if ($changePercent > 0) {
            $trend = "UP";
        } elseif ($changePercent < 0) {
            $trend = "DOWN";
        }

        /**
         * =========================================================
         * FINAL RESPONSE SHAPE
         * =========================================================
         */
        return [
            'id' => $this->id,

            /**
             * ITEM (NEW - REQUIRED)
             */
           // ✅ SINGLE ITEM OBJECT (WHAT YOU WANT)
           'item' => $this->item ? [
            'id' => $this->item->id,
            'name' => $this->item->name,
            'unit' => $this->item->unit,
        ] : null,

            /**
             * CITY
             */
            'cityId' => $cityId,
            'cityName' => $this->city?->name ?? null,

            /**
             * UNIT
             */
            'unit' => $this->marketItem?->unit ?? 'kg',

            /**
             * PRICES
             */
            'currentPrice' => $currentPrice,
            'previousPrice' => $previousPrice,
            'changePercent' => round($changePercent, 2),

            /**
             * TREND
             */
            'trend' => $trend,

            /**
             * CHART DATA
             */
            'history' => $history,
        ];
    }
}