<?php

namespace Laravel\Serverless\Console;

use Illuminate\Console\Command;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Queue\Jobs\SyncJob;

class WorkCommand extends Command
{
    protected $signature = 'serverless:work {message : The Base64 encoded message payload}';

    protected $worker;

    public function __construct(Worker $worker)
    {
        parent::__construct();
        $this->worker = $worker;
    }

    public function handle()
    {
        $this->worker->setCache($this->laravel['cache']->driver());

        $queueConnection = config('queue.default');
        $queueName       = config("queue.$queueConnection.queue", 'default');

        // todo: dehydrate it as proper job object
        $job = new SyncJob($this->laravel, $this->message(), $queueConnection, $queueName);

        // fixme: get memory/timeout from env
        try {
            $this->worker->process(
                $queueConnection, $job,
                new WorkerOptions(
                    $delay = 0,
                    $memory = 512,
                    $timeout = 0,
                    $sleep = 0,
                    $maxTries = 0
                ));
        } catch (\Exception $e) {
            $this->laravel['queue.failer']->log(
                $queueConnection, $queueName, $job->getRawBody(), $e
            );
            return 1;
        }

        return 0;
    }

    protected function message()
    {
        return tap(base64_decode($this->argument('message'), true), function ($message) {
            if ($message === false) {
                throw new \InvalidArgumentException("Unable to unserialize message.");
            }
            return $message;
        });
    }
}
