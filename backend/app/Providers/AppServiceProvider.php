<?php

namespace App\Providers;

use App\Models\Supplier;
use App\Observers\SupplierObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Supplier::observe(SupplierObserver::class);
    }
}
