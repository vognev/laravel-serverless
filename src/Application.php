<?php

namespace Laravel\Serverless;

use Illuminate\Support\Facades\Facade;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

use Illuminate\Contracts\Console\Kernel     as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel        as HttpKernel;

/**
 * @method static \Illuminate\Contracts\Foundation\Application useStoragePath(string $path)
 */
class Application extends Facade
{
    protected static function getFacadeAccessor()
    {
        return app();
    }

    public static function artisan(array $args) : array
    {
        /** @var ConsoleKernel $kernel */
        $kernel = self::getFacadeRoot()->make(ConsoleKernel::class);

        $status = $kernel->handle(
            $input  = new ArgvInput($args),
            $output = new BufferedOutput()
        );

        try {
            return [$status, $output->fetch()];
        } finally {
            $kernel->terminate($input, $status);
        }
    }

    public static function website(\Illuminate\Http\Request $request) : \Symfony\Component\HttpFoundation\Response
    {
        /** @var HttpKernel $kernel */
        $kernel = self::getFacadeRoot()->make(HttpKernel::class);

        $response = $kernel->handle($request);

        try {
            return $response;
        } finally {
            $kernel->terminate($request, $response);
        }
    }
}
