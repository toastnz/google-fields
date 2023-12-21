Map field
```
use Goldfinch\GoogleFields\Forms\MapField;

private static $db = [
  'Map' => 'Map',
];

// basic use:
MapField::create('Map', 'Map')
  //->mapHideSearch(),
  //->mapHideExtra(),
  //->mapReadonly(),

// set default settings:
MapField::create('Map', 'Map')->setSettings([
    'lng' => 168.7439017,
    'lat' => -45.0136784,
    'zoom' => 10,
])
```

Place field
```
use Goldfinch\GoogleFields\Forms\PlaceField;

private static $db = [
  'Place' => 'Place',
];

// basic use:
PlaceField::create('Place', 'Place')
  //->placeHidePreview(),

// set default settings:
PlaceField::create('Place', 'Place')->setSettings([
    'country' => 'ru',
])
```

```
Map:
<div>$Map</div>
<div>$Map.Link</div>
<div>$Map.Longitude</div>
<div>$Map.Latitude</div>
<div>$Map.Zoom</div>

Place:
<div><b>Address</b>: $Place.Address</div>
<div><b>Data</b>: $Place.Data</div>
<div><b>Link</b>: $Place.Link</div>

<div><b>Subpremise</b>: $Place.Subpremise</div>
<div><b>StreetNumber</b>: $Place.StreetNumber</div>
<div><b>StreetName</b>: $Place.StreetName</div>
<div><b>Suburb</b>: $Place.Suburb</div>
<div><b>Subarea</b>: $Place.Subarea</div>
<div><b>Region</b>: $Place.Region</div>
<div><b>District</b>: $Place.District</div>
<div><b>Country</b>: $Place.Country</div>
<div><b>Postcode</b>: $Place.Postcode</div>

<div><b>Place name</b>: $Place.PlaceName</div>
<div><b>Latitude</b>: $Place.Latitude</div>
<div><b>Longitude</b>: $Place.Longitude</div>
```
