<?php

namespace Tabuna\Map\Tests\Dummy;

class CustomMapperStub
{
    public function __invoke(mixed $item, $target)
    {
        $obj = new DummyAirport();
        $obj->code = 'custom-mapped';
        $obj->city = 'custom-mapped';

        return $obj;
    }
}
