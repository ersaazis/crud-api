<?php

namespace ersaazis\crudapi;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Container\Container;
use ersaazis\crudapi\Console\Commands\ApiCrudMaker;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider 
{

    public function boot(Factory $view, Dispatcher $events, Repository $config) 
    {     
        $this->loadTranslations();

        $this->registerCommands();
    }

    private function loadTranslations()
    {
        $translationsPath = $this->packagePath('resources/lang');

        $this->loadTranslationsFrom($translationsPath, 'crud-api');

        $this->publishes([
            $translationsPath => resource_path('lang/vendor/crud-api'),
        ], 'translations');
    }

    private function packagePath($path)
    {
        return __DIR__."/../$path";
    }

    private function registerCommands()
    {
        $this->commands(ApiCrudMaker::class);
    }

}
