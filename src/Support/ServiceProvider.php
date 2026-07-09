<?php

namespace AkosNoavek\DataExtractor\Support;

use AkosNoavek\DataExtractor\Facades\DataExtractor;
use \Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
  /**
   * Bootstrap the application services.
   */
  public function boot(): void
  {
    if ($this->app->runningInConsole()) {
      $this->publishes([
        __DIR__ . '/../../Config/config.php' => config_path('data_extractor.php'),
      ], 'config');
    }
  }

  /**
   * Register the application services.
   */
  public function register(): void
  {
    $this->mergeConfigFrom(__DIR__ . '/../../Config/config.php', 'form-components');

    $this->app->bind('data_extractor', function () {
      return new DataExtractor;
    });
  }
}
