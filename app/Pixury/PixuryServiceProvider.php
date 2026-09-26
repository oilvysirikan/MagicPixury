<?php

declare(strict_types=1);

namespace App\Pixury;

use App\Pixury\Creative\GenerationService;
use App\Pixury\Templates\TemplateRepository;
use App\Pixury\Templates\TemplateService;
use Illuminate\Support\ServiceProvider;

class PixuryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TemplateRepository::class, fn () => new TemplateRepository());
        $this->app->singleton(TemplateService::class, fn ($app) => new TemplateService($app->make(TemplateRepository::class)));
        $this->app->singleton(GenerationService::class, fn () => new GenerationService());
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('routes/pixury.php'));
        $this->loadViewsFrom(__DIR__ . '/../../resources/views/default/pixury', 'pixury');
    }
}