<?php

namespace App\Providers;

use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Number;
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
        // In production every generated URL — including Filament's asset links —
        // must be https, or a browser on the https panel blocks the http assets
        // as mixed content. Trusting the proxy (bootstrap/app.php) usually
        // suffices; forcing the scheme here is the belt-and-braces guarantee.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Number::useLocale('en');

        Table::configureUsing(function (Table $table) {
            $table->defaultNumberLocale('en');
        });

        Schema::configureUsing(function (Schema $schema) {
            $schema->defaultNumberLocale('en');
        });
    }
}
