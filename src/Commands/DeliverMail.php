<?php

namespace RedFern\MailFactory\Commands;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Facade\Ignition\Support\ComposerClassMap;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;

class DeliverMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:deliver';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send mail out for delivery';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws Exception
     */
    public function handle()
    {
        $mailables = $this->getMailables();

        $mailable = $this->choice('Which mailable would you like to deliver?', $mailables);

        $eloquentFactory = app(EloquentFactory::class);

        if (!config()->has('mailfactory.mailables.'.$mailable)) {
            throw new Exception("No mailable called {$mailable} is registered in config/mailfactory.php file");
        }

        $dependencies = collect(config('mailfactory.mailables.'.$mailable));

        DB::beginTransaction();

        $args = $dependencies->map(static function ($dependency) use ($eloquentFactory) {
            $factoryStates = [];

            if (is_array($dependency) && array_key_exists('states', $dependency)) {
                $factoryStates = $dependency['states'];
                $dependency = $dependency['class'];
            }

            if (is_string($dependency) && class_exists($dependency)) {
                if (isset($eloquentFactory[$dependency])) {
                    return factory($dependency)->states($factoryStates)->create();
                }

                return app($dependency);
            }

            return $dependency;
        });

        Mail::to(config('mailfactory.to'))->send(new $mailable(...$args));

        DB::rollBack();
    }

    private function getMailables()
    {
        $namespaces = collect(array_keys((new ComposerClassMap)->listClasses()));

        return $namespaces->filter(static function ($item) {
            return Str::startsWith($item, "App\\Mail\\");
        })->values()->toArray();
    }
}
