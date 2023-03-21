<?php

namespace GptHelperForLaravel;

use Illuminate\Console\Application;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Input\InputOption;

class GptServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('files', function () {
            return new GptFilesystem;
        });

        Application::starting(function (Application $artisan) {
            $artisan->getDefinition()->addOption(
                new InputOption('--prompt', null, InputOption::VALUE_OPTIONAL, 'Enter in any details you wish to provide about the file.')
            );
        });

        $this->mergeConfigFrom(__DIR__ . '/../config/gpt-helper.php', 'gpt-helper');
        $this->loadTranslationsFrom(__DIR__ . '/../lang/prompts.php', 'gpt-helper');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/gpt-helper.php' => config_path('prompts.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/gpt-helper'),
        ], 'lang');
    }
}
