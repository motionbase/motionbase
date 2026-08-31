<?php

namespace App\Providers;

use App\Models\Section;
use App\Models\Topic;
use App\Policies\SectionPolicy;
use App\Policies\TopicPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

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
        Gate::policy(Topic::class, TopicPolicy::class);
        Gate::policy(Section::class, SectionPolicy::class);

        // Passport binds AuthorizationViewResponse only from inside this call,
        // so without it GET /oauth/authorize dies with "not instantiable" the
        // moment a real client starts the consent flow. laravel/mcp ships the
        // view under its own namespace, so nothing needs publishing.
        Passport::authorizationView('mcp::authorize');
    }
}
