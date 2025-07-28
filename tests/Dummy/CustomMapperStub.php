<?php

namespace Tabuna\Map\Tests\Dummy;

class CustomMapperStub
{
    public function map($item, $targetClass)
    {
        $obj = new $targetClass();
        $obj->code = 'custom-mapped';
        $obj->city = 'custom-mapped';

        return $obj;
    }
}
