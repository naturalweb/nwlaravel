<?php

namespace Tests;

use Mockery;
use PHPUnit_Framework_TestCase;
use Illuminate\Container\Container;

class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * The Illuminate application instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $app;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        date_default_timezone_set('America/Sao_Paulo');
        
        $this->app = new Container;
        Container::setInstance($this->app);
    }

    /**
     * Tear Down
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Mockery::close();

        if ($this->app) {
            $this->app->flush();
            $this->app = null;
        }
    }
}
