<?php

declare(strict_types=1);

namespace Tabuna\UI\Tests;

use Tabuna\UI\Mapper;
use Tabuna\UI\Tests\Dummy\CustomMapperStub;
use Tabuna\UI\Tests\Dummy\DummyAirport;
use Tabuna\UI\Tests\Dummy\EloquentAirportStub;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Orchestra\Testbench\TestCase;

class MapperTest extends TestCase
{
    public function testItMapsArrayToObjectProperties(): void
    {
        $data = ['code' => 'SVO', 'city' => 'Moscow'];

        $mapped = Mapper::map($data)->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirport::class, $mapped);
        $this->assertSame('SVO', $mapped->code);
        $this->assertSame('Moscow', $mapped->city);
    }

    public function testItMapsCollectionOfArrays(): void
    {
        $data = [
            ['code' => 'SVO', 'city' => 'Moscow'],
            ['code' => 'JFK', 'city' => 'New York'],
        ];

        $mapped = Mapper::map($data)->collection()->to(DummyAirport::class);

        $this->assertInstanceOf(Collection::class, $mapped);
        $this->assertCount(2, $mapped);
        $this->assertSame('JFK', $mapped[1]->code);
    }

    public function testItMapsRequestToModel(): void
    {
        $request = Request::create('/fake', 'POST', [
            'code' => 'SVO',
            'city' => 'Moscow',
        ]);

        $mapped = Mapper::map($request)->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirport::class, $mapped);
        $this->assertSame('Moscow', $mapped->city);
    }

    public function testItFillsEloquentModelAttributes(): void
    {
        $data = ['code' => 'SVO', 'city' => 'Moscow'];

        $mapped = Mapper::map($data)->to(EloquentAirportStub::class);

        $this->assertInstanceOf(EloquentAirportStub::class, $mapped);
        $this->assertSame('SVO', $mapped->code);
        $this->assertSame('Moscow', $mapped->city);
    }

    public function testItUsesCustomMapperClass(): void
    {
        $data = ['code' => 'SVO', 'city' => 'Moscow'];

        $mapped = Mapper::map($data)
            ->with(CustomMapperStub::class)
            ->to(DummyAirport::class);

        $this->assertSame('custom-mapped', $mapped->code);
        $this->assertSame('custom-mapped', $mapped->city);
    }

    public function testItUsesCustomMapperClosure(): void
    {
        $data = ['code' => 'SVO', 'city' => 'Moscow'];

        $mapped = Mapper::map($data)
            ->with(function ($mapper, $item) {
                $obj = new DummyAirport();
                $obj->code = 'closure-mapped';
                $obj->city = 'closure-mapped';

                return $obj;
            })
            ->to(DummyAirport::class);

        $this->assertSame('closure-mapped', $mapped->code);
        $this->assertSame('closure-mapped', $mapped->city);
    }

    public function testHelperFunctionMapsRequestToObject(): void
    {
        $request = Request::create('/fake', 'POST', [
            'code' => 'LAX',
            'city' => 'Los Angeles',
        ]);

        $airport = map($request)->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirport::class, $airport);
        $this->assertSame('LAX', $airport->code);
        $this->assertSame('Los Angeles', $airport->city);
    }
}
