<?php

namespace GptHelperForLaravel;

use GptHelperForLaravel\Commands\GenerateCodeCommand;
use GptHelperForLaravel\Commands\PredictFileCommand;
use GptHelperForLaravel\Commands\RefactorCodeCommand;
use GptHelperForLaravel\Support\SummarizeFile;
use Illuminate\Console\Application;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Input\InputOption;

class GptServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('summarize-file', function ($app) {
            return new SummarizeFile();
        });

        $this->app->singleton('files', function () {
            return new GptFilesystem;
        });

        Application::starting(function (Application $artisan) {
            $artisan->getDefinition()->addOptions([
                new InputOption('--prompt', null, InputOption::VALUE_OPTIONAL, 'Enter in any details you wish to provide about the file.'),
                new InputOption('--files', null, InputOption::VALUE_OPTIONAL, 'Enter any reference files you wish to include via a comma separated list.'),
            ]);
        });

        $this->mergeConfigFrom(__DIR__ . '/../config/gpt-helper.php', 'gpt-helper');

        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'gpt-helper');
//        $this->app['translator']->addNamespace('gpt-helper', __DIR__ . '/../lang');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PredictFileCommand::class,
                GenerateCodeCommand::class,
                RefactorCodeCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../config/gpt-helper.php' => config_path('prompts.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/gpt-helper'),
        ], 'lang');
    }
}
