<?php

namespace App\DTOs\Market;

class PriceHistoryQuery
{
    public function __construct(
        public string $market_item_id,
        public ?string $from_date = null,
        public ?string $to_date = null,
    ) {}
}