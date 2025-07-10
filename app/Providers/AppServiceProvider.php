<?php

namespace App\Providers;

use App\Models\Documentation;
use App\Models\Note;
use App\Observers\DocumentationObserver;
use App\Observers\NoteObserver;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
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
        \Carbon\Carbon::setLocale('id');
        Documentation::observe(DocumentationObserver::class);
        Note::observe(NoteObserver::class);

    }

    // App\Providers\RouteServiceProvider.php

    public const HOME = '/redirect-after-login';

}
