<?php

namespace Tabuna\Map\Tests\Dummy;

use Illuminate\Database\Eloquent\Model;

class EloquentAirportStub extends Model
{
    protected $fillable = ['code', 'city'];
}
