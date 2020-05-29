<?php

namespace RedFern\MailFactory;

use Illuminate\Support\ServiceProvider;
use RedFern\MailFactory\Commands\DeliverMail;

class MailFactoryServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/mailfactory.php' => config_path('mailfactory.php'),
        ], 'config');
    }

    public function register()
    {
        $this->app->bind('command.mail:deliver', DeliverMail::class);

        $this->commands([
            'command.mail:deliver',
        ]);
    }
}
