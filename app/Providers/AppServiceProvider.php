<?php

namespace App\Providers;

use App\Models\DataPendaftar;
use App\Models\PengaturanSpk;
use App\Models\User;
use App\Policies\DataPendaftarPolicy;
use App\Policies\PengaturanSpkPolicy;
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
        Gate::policy(DataPendaftar::class, DataPendaftarPolicy::class);
        Gate::policy(PengaturanSpk::class, PengaturanSpkPolicy::class);

        Gate::define('kelola-spk', static function (User $user): bool {
            return $user->isAdmin();
        });

        Gate::define('lihat-analisis', static function (User $user): bool {
            return $user->isAdmin() || $user->isPimpinan();
        });
    }
}
