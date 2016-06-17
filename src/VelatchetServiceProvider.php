<?php

namespace Inoplate\Velatchet;

use Illuminate\Support\ServiceProvider;

class VelatchetServiceProvider extends ServiceProvider
{
    /**
     * Boot package
     * 
     * @return void
     */
    public function boot()
    {
        $this->loadConfiguration();
        $this->registerRatchetBroadcaster();
        $this->registerConsoleCommand();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Inoplate\Velatchet\TopicHandlers', function($app) {
            return new TopicHandlers($app);
        });

        $this->app->alias('Inoplate\Velatchet\TopicHandlers', 'ratchet.handlers');
    }

    /**
     * Register ratchet broadcaster
     * 
     * @return void
     */
    protected function registerRatchetBroadcaster()
    {
        $this->app['Illuminate\Broadcasting\BroadcastManager']->extend('ratchet', function($app) {
            $topicHandlers = $app['Inoplate\Velatchet\TopicHandlers'];
            $pusher = new Pusher($app, $topicHandlers);
            $host = $app['config']->get('inoplate.velatchet.websocket.host');

            return new Broadcaster($pusher, $host);
        });
    }

    /**
     * Load package configuration
     * 
     * @return void
     */
    protected function loadConfiguration()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/velatchet.php', 'inoplate.velatchet'
        );

        $this->publishes([
            __DIR__.'/../config/velatchet.php' => config_path('inoplate/velatchet.php'),
        ], 'config');
    }

    /**
     * Register console command
     * 
     * @return void
     */
    protected function registerConsoleCommand()
    {
        $this->commands( \Inoplate\Velatchet\PushServer::class );
    }
}