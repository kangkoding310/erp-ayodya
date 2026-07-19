<?php

namespace App\Providers;

use App\Models\ExpenseReport;
use App\Models\ExpenseReportLine;
use App\Models\PurchaseRequestLine;
use App\Models\PurchaseRfq;
use App\Observers\ExpenseReportObserver;
use App\Observers\PurchaseRequestObserver;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Relation::morphMap([
            'purchase' => PurchaseRfq::class,
            'expense' => ExpenseReport::class,
        ]);

        PurchaseRequestLine::observe(PurchaseRequestObserver::class);
        ExpenseReportLine::observe(ExpenseReportObserver::class);
    }
}
