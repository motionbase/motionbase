<?php

namespace App\Providers;

use App\Models\Section;
use App\Models\Topic;
use App\Policies\SectionPolicy;
use App\Policies\TopicPolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Topic::class, TopicPolicy::class);
        Gate::policy(Section::class, SectionPolicy::class);
    }
}
