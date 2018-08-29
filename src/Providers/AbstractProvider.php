<?php

namespace Providers;

use Models\Application;
use Silex\ServiceProviderInterface;

abstract class AbstractProvider implements ServiceProviderInterface
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param \Silex\Application $app An Application instance
     */
    public function register(\Silex\Application $app)
    {
        $this->app = $app;
        return $this->registerExtension();
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     *
     * @param \Silex\Application $app An Application instance
     */
    public function boot(\Silex\Application $app)
    {
        $this->bootExtension();
    }

    abstract public function registerExtension();

    public function bootExtension() {

    }
}