<?php

namespace App\Providers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
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

        // Define our task authorization rule
        Gate::define('modify', function (User $user, Task $task) {
            return $user->id === $task->user_id;
        });

        // Throws an immediate error in your local terminal if you accidentally write an N+1 loop
        Model::preventLazyLoading(! app()->isProduction());
    }
}
