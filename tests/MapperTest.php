<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Orchestra\Testbench\TestCase;
use Tabuna\Map\Mapper;
use Tabuna\Map\Tests\Dummy\CustomMapperStub;
use Tabuna\Map\Tests\Dummy\DummyAirport;
use Tabuna\Map\Tests\Dummy\EloquentAirportStub;

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

    public function testItConvertsMappedObjectToArray(): void
    {
        $data = ['code' => 'SVO', 'city' => 'Moscow'];

        $array = Mapper::map($data)->toArray();

        $this->assertIsArray($array);
        $this->assertSame(['code' => 'SVO', 'city' => 'Moscow'], $array);
    }

    public function testItConvertsMappedCollectionToArray(): void
    {
        $data = [
            ['code' => 'SVO', 'city' => 'Moscow'],
            ['code' => 'JFK', 'city' => 'New York'],
        ];

        $array = Mapper::map($data)->collection()->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertSame('New York', $array[1]['city']);
    }

    public function testItConvertsMappedObjectToJson(): void
    {
        $data = ['code' => 'SVO', 'city' => 'Moscow'];

        $json = Mapper::map($data)->toJson();

        $this->assertJson($json);
        $this->assertJsonStringEqualsJsonString('{"code":"SVO","city":"Moscow"}', $json);
    }

    public function testItConvertsMappedCollectionToJson(): void
    {
        $data = [
            ['code' => 'SVO', 'city' => 'Moscow'],
            ['code' => 'JFK', 'city' => 'New York'],
        ];

        $json = Mapper::map($data)->collection()->toJson();

        $this->assertJson($json);
        $expected = json_encode($data, JSON_THROW_ON_ERROR);
        $this->assertJsonStringEqualsJsonString($expected, $json);
    }

    public function testCollectionModeReturnsLaravelCollection(): void
    {
        $data = [
            ['code' => 'SVO', 'city' => 'Moscow'],
            ['code' => 'JFK', 'city' => 'New York'],
        ];

        $result = Mapper::map($data)
            ->collection()
            ->to(DummyAirport::class);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertContainsOnlyInstancesOf(DummyAirport::class, $result);
    }

    public function testItDoesNotWrapExistingCollectionIntoAnotherCollection(): void
    {
        $originalCollection = collect([
            ['code' => 'SVO', 'city' => 'Moscow'],
            ['code' => 'JFK', 'city' => 'New York'],
        ]);

        $mapped = Mapper::map($originalCollection)
            ->collection()
            ->to(DummyAirport::class);

        $this->assertInstanceOf(Collection::class, $mapped);
        $this->assertInstanceOf(DummyAirport::class, $mapped->first());
    }
}
