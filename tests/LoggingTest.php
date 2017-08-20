<?php

namespace Maklad\Permission\Test;

use Monolog\Logger;

class LoggingTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    /** @test */
    //    public function it_logs_when_config_is_set_to_true()
    //    {
    //        FIXME Cannot generate exception
    //        $this->app['config']->set('permission.log_registration_exception', true);
    //
    //        $this->reloadPermissions();
    //
    //        $this->assertLogged('Could not register permissions', Logger::ALERT);
    //    }

    /** @test */
    public function it_doesnt_log_when_config_is_set_to_false()
    {
        $this->app['config']->set('permission.log_registration_exception', false);

        $this->reloadPermissions();

        $this->assertNotLogged('Could not register permissions', Logger::ALERT);
    }
}
