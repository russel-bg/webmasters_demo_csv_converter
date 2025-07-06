<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\CsvFileRepositoryInterface;
use App\Repositories\CsvFileRepository;
use App\Services\CsvFileServiceInterface;
use App\Services\CsvFileService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CsvFileRepositoryInterface::class, CsvFileRepository::class);
        $this->app->bind(CsvFileServiceInterface::class, CsvFileService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
