<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

// Models
use App\Models\City;
use App\Models\Subcity;
use App\Models\Wereda;
use App\Models\User;
use App\Models\Business;
use App\Models\MarketCategory;
use App\Models\MarketItem;
use App\Models\Inspection;
use App\Models\InspectionClosureRequest;

// Policies
use App\Policies\CityPolicy;
use App\Policies\SubCityPolicy;
use App\Policies\WeredaPolicy;
use App\Policies\UserPolicy;
use App\Policies\BusinessPolicy;
use App\Policies\MarketCategoryPolicy;
use App\Policies\MarketItemPolicy;
use App\Policies\InspectionPolicy;
use App\Policies\InspectionClosureRequestPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Policy mappings.
     */
    protected $policies = [

        /*
        |--------------------------------------------------------------------------
        | Administrative Geography
        |--------------------------------------------------------------------------
        */

        City::class => CityPolicy::class,

        Subcity::class => SubCityPolicy::class,

        Wereda::class => WeredaPolicy::class,

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        User::class => UserPolicy::class,

        /*
        |--------------------------------------------------------------------------
        | Businesses
        |--------------------------------------------------------------------------
        */

        Business::class => BusinessPolicy::class,

        /*
        |--------------------------------------------------------------------------
        | Market
        |--------------------------------------------------------------------------
        */

        MarketCategory::class => MarketCategoryPolicy::class,

        MarketItem::class => MarketItemPolicy::class,

        /*
        |--------------------------------------------------------------------------
        | Inspection
        |--------------------------------------------------------------------------
        */

        Inspection::class => InspectionPolicy::class,

        InspectionClosureRequest::class
            => InspectionClosureRequestPolicy::class,
    ];

    /**
     * Register policies.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}