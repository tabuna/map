<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Orchestra\Testbench\TestCase;
use Tabuna\Map\Mapper;
use Tabuna\Map\Tests\Dummy\CustomMapperStub;
use Tabuna\Map\Tests\Dummy\DummyAirport;
use Tabuna\Map\Tests\Dummy\DummyAirportHook;
use Tabuna\Map\Tests\Dummy\DummyAirportSetter;
use Tabuna\Map\Tests\Dummy\DummyWithContainer;
use Tabuna\Map\Tests\Dummy\EloquentAirportStub;

class MapperTest extends TestCase
{
    public function testItMapsArrayToObjectProperties(): void
    {
        $data = ['code' => 'LPK', 'city' => 'Lipetsk'];

        $mapped = Mapper::map($data)->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirport::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
        $this->assertSame('Lipetsk', $mapped->city);
    }

    public function testItMapsArrayToObjectHookProperties(): void
    {
        $this->markTestSkippedUnless(
            version_compare(PHP_VERSION, '8.4', '>'),
            'PHP version >= 8.4 or higher is required.'
        );

        $data = ['code' => 'lpk', 'city' => 'Lipetsk'];

        $mapped = Mapper::map($data)->to(DummyAirportHook::class);

        $this->assertInstanceOf(DummyAirportHook::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
    }

    public function testItMapsArrayToObjectWithOutPartProperties(): void
    {
        $data = ['code' => 'LPK'];

        $mapped = Mapper::map($data)->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirport::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
    }

    public function testItMapsCollectionOfArrays(): void
    {
        $data = [
            ['code' => 'LPK', 'city' => 'Lipetsk'],
            ['code' => 'SVO', 'city' => 'Moscow'],
        ];

        $mapped = Mapper::map($data)->collection()->to(DummyAirport::class);

        $this->assertInstanceOf(Collection::class, $mapped);
        $this->assertCount(2, $mapped);
        $this->assertSame('SVO', $mapped[1]->code);
    }

    public function testItMapsRequestToModel(): void
    {
        $request = Request::create('/fake', 'POST', [
            'code' => 'LPK',
            'city' => 'Lipetsk',
        ]);

        $mapped = Mapper::map($request)->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirport::class, $mapped);
        $this->assertSame('Lipetsk', $mapped->city);
    }

    public function testItFillsEloquentModelAttributes(): void
    {
        $data = ['code' => 'LPK', 'city' => 'Lipetsk'];

        $mapped = Mapper::map($data)->to(EloquentAirportStub::class);

        $this->assertInstanceOf(EloquentAirportStub::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
        $this->assertSame('Lipetsk', $mapped->city);
    }

    /*
    public function testItUsesCustomMapperClass(): void
    {
        $data = ['code' => 'LPK', 'city' => 'Lipetsk'];

        $mapped = Mapper::map($data)
            ->with(CustomMapperStub::class)
            ->to(DummyAirport::class);

        $this->assertSame('custom-mapped', $mapped->code);
        $this->assertSame('custom-mapped', $mapped->city);
    }
*/
    /*
    public function testItUsesCustomMapperClosure(): void
    {
        $data = ['code' => 'LPK', 'city' => 'Lipetsk'];

        $mapped = Mapper::map($data)
            ->with(function ($mapper, $item) {

                dd($mapper, $item);
                $obj = new DummyAirport();
                $obj->code = 'closure-mapped';
                $obj->city = 'closure-mapped';

                return $obj;
            })
            ->to(DummyAirport::class);

        $this->assertSame('closure-mapped', $mapped->code);
        $this->assertSame('closure-mapped', $mapped->city);
    }
*/

    public function testHelperFunctionMapsRequestToObject(): void
    {
        $request = Request::create('/fake', 'POST', [
            'code' => 'LED',
            'city' => 'Saint Petersburg',
        ]);

        $airport = map($request)->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirport::class, $airport);
        $this->assertSame('LED', $airport->code);
        $this->assertSame('Saint Petersburg', $airport->city);
    }

    public function testItConvertsMappedObjectToArray(): void
    {
        $data = ['code' => 'LPK', 'city' => 'Lipetsk'];

        $array = Mapper::map($data)->toArray();

        $this->assertIsArray($array);
        $this->assertSame(['code' => 'LPK', 'city' => 'Lipetsk'], $array);
    }

    public function testItConvertsMappedCollectionToArray(): void
    {
        $data = [
            ['code' => 'LPK', 'city' => 'Lipetsk'],
            ['code' => 'SVO', 'city' => 'Moscow'],
        ];

        $array = Mapper::map($data)->collection()->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertSame('Moscow', $array[1]['city']);
    }

    public function testItConvertsMappedObjectToJson(): void
    {
        $data = ['code' => 'LPK', 'city' => 'Lipetsk'];

        $json = Mapper::map($data)->toJson();

        $this->assertJson($json);
        $this->assertJsonStringEqualsJsonString('{"code":"LPK","city":"Lipetsk"}', $json);
    }

    public function testItConvertsMappedCollectionToJson(): void
    {
        $data = [
            ['code' => 'LPK', 'city' => 'Lipetsk'],
            ['code' => 'SVO', 'city' => 'Moscow'],
        ];

        $json = Mapper::map($data)->collection()->toJson();

        $this->assertJson($json);
        $expected = json_encode($data, JSON_THROW_ON_ERROR);
        $this->assertJsonStringEqualsJsonString($expected, $json);
    }

    public function testCollectionModeReturnsLaravelCollection(): void
    {
        $data = [
            ['code' => 'LPK', 'city' => 'Lipetsk'],
            ['code' => 'SVO', 'city' => 'Moscow'],
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
            ['code' => 'LPK', 'city' => 'Lipetsk'],
            ['code' => 'SVO', 'city' => 'Moscow'],
        ]);

        $mapped = Mapper::map($originalCollection)
            ->collection()
            ->to(DummyAirport::class);

        $this->assertInstanceOf(Collection::class, $mapped);
        $this->assertInstanceOf(DummyAirport::class, $mapped->first());
    }


    public function testItMapsWithContainerProperties(): void
    {
        $data = ['code' => 'LPK', 'city' => 'Lipetsk'];

        $mapped = Mapper::map($data)->to(DummyWithContainer::class);

        $this->assertInstanceOf(DummyWithContainer::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
        $this->assertSame('Lipetsk', $mapped->city);
        $this->assertNotNull($mapped->version);
    }

    public function testItMapsOverriteConstructorProperties(): void
    {
        $data = ['code' => 'LPK', 'city' => 'Lipetsk', 'version' => 2];

        $mapped = Mapper::map($data)->to(DummyWithContainer::class);

        $this->assertInstanceOf(DummyWithContainer::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
        $this->assertSame('Lipetsk', $mapped->city);
        $this->assertSame(2, $mapped->version);
    }

    public function testItIgnoresExtraFields(): void
    {
        $data = ['code' => 'LPK', 'city' => 'Lipetsk', 'extra' => 'ignored'];

        $mapped = Mapper::map($data)->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirport::class, $mapped);
        $this->assertFalse(property_exists($mapped, 'extra'));
    }

    public function testItParsesValidJsonString(): void
    {
        $json = '{"code": "LPK", "city": "Lipetsk"}';

        $mapped = Mapper::map($json)->toArray();

        $this->assertIsArray($mapped);
        $this->assertSame('LPK', $mapped['code']);
        $this->assertSame('Lipetsk', $mapped['city']);
    }

    public function testItThrowsOnInvalidJson(): void
    {
        $this->expectException(\JsonException::class);

        $invalidJson = '{"code": "LPK", "city": "Lipetsk"';

        Mapper::map($invalidJson)->toArray();
    }
}
