<?php

namespace Tabuna\Map\Tests\Dummy;

use Illuminate\Foundation\Application;

class DummyWithContainer extends DummyAirport
{
    public $version;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
       $this->version = $application->version();
    }
}