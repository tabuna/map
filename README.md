# Laravel Mapper

A simple and powerful object mapper for Laravel. 
Easily map arrays, requests, or collections to DTOs, Eloquent models, or custom objects.

## Usage

### Map to a model or DTO

```php
use Illuminate\Http\Request;
use App\Models\Airport;

class AirportController extends Controller
{
    public function store(Request $request)
    {
        $airport = map($request)->to(Airport::class);

        $airport->save();

        return response()->json($airport);
    }
}
````

### Map a collection

```php
$data = [
    ['code' => 'SVO', 'city' => 'Moscow'],
    ['code' => 'JFK', 'city' => 'New York'],
];

$airports = map($data)
    ->collection()
    ->to(Airport::class);
```

### With a custom mapper

```php
$airport = map($data)
    ->with(CustomAirportMapper::class)
    ->to(Airport::class);
```

Or via closure:

```php
$airport = map($data)
    ->with(fn ($mapper, $data) => new Airport([
        'code' => strtoupper($data['code'])
    ]))
    ->to(Airport::class);
```

### Serialize

```php
$array = map($airport)->toArray();
$json = map($airport)->toJson();
```
