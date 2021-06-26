<?php

namespace Joy\VoyagerApi\Tests;

use Dotenv\Dotenv;
use Joy\VoyagerApi\VoyagerApiServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    public function setUp(): void
    {
        $this->loadEnvironmentVariables();

        parent::setUp();

        $this->setUpDatabase($this->app);
    }

    protected function loadEnvironmentVariables()
    {
        if (!file_exists(__DIR__ . '/../.env')) {
            return;
        }

        $dotEnv = Dotenv::createImmutable(__DIR__ . '/..');

        $dotEnv->load();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        $serviceProviders = [
            VoyagerApiServiceProvider::class,
        ];

        return $serviceProviders;
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpDatabase($app)
    {
    }
}
