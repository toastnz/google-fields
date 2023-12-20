.env
```
APP_GOOGLE_MAPS_KEY=""
```

## Map field
Required [Maps JavaScriptAPI](https://console.cloud.google.com/apis/library/maps-backend.googleapis.com)
```
use Goldfinch\GoogleFields\Forms\MapField;

private static $db = [
  'Map' => 'Map',
];

// basic use:
MapField::create('Map', 'Map')

// set default settings:
MapField::create('Map', 'Map')->setSettings([
    'lng' => 168.73148910089623,
    'lat' => -45.01597101207079,
    'zoom' => 10,
])
```

## Place field
Required [Places API](https://console.cloud.google.com/apis/library/places-backend.googleapis.com)
```
use Goldfinch\GoogleFields\Forms\PlaceField;

private static $db = [
  'Place' => 'Place',
];

// basic use:
PlaceField::create('Place', 'Place')

// set default settings:
PlaceField::create('Place', 'Place')->setSettings([
    'country' => 'ru',
])
```
