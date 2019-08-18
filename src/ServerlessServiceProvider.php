<?php

namespace Laravel\Serverless;

use Illuminate\Support\ServiceProvider;

class ServerlessServiceProvider extends ServiceProvider
{
    public function boot()
    {

    }

    public function register()
    {
        $this->commands([
            Console\WorkCommand::class,
        ]);
    }

    public function provides()
    {
        return [

        ];
    }
}
