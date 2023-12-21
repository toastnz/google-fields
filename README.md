
# 🦅 Google Fields for Silverstripe

[![Silverstripe Version](https://img.shields.io/badge/Silverstripe-5.1-005ae1.svg?labelColor=white&logoColor=ffffff&logo=data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDEuMDkxIDU4LjU1NSIgZmlsbD0iIzAwNWFlMSIgeG1sbnM6dj0iaHR0cHM6Ly92ZWN0YS5pby9uYW5vIj48cGF0aCBkPSJNNTAuMDE1IDUuODU4bC0yMS4yODMgMTQuOWE2LjUgNi41IDAgMCAwIDcuNDQ4IDEwLjY1NGwyMS4yODMtMTQuOWM4LjgxMy02LjE3IDIwLjk2LTQuMDI4IDI3LjEzIDQuNzg2czQuMDI4IDIwLjk2LTQuNzg1IDI3LjEzbC02LjY5MSA0LjY3NmM1LjU0MiA5LjQxOCAxOC4wNzggNS40NTUgMjMuNzczLTQuNjU0QTMyLjQ3IDMyLjQ3IDAgMCAwIDUwLjAxNSA1Ljg2MnptMS4wNTggNDYuODI3bDIxLjI4NC0xNC45YTYuNSA2LjUgMCAxIDAtNy40NDktMTAuNjUzTDQzLjYyMyA0Mi4wMjhjLTguODEzIDYuMTctMjAuOTU5IDQuMDI5LTI3LjEyOS00Ljc4NHMtNC4wMjktMjAuOTU5IDQuNzg0LTI3LjEyOWw2LjY5MS00LjY3NkMyMi40My0zLjk3NiA5Ljg5NC0uMDEzIDQuMTk4IDEwLjA5NmEzMi40NyAzMi40NyAwIDAgMCA0Ni44NzUgNDIuNTkyeiIvPjwvc3ZnPg==)](https://packagist.org/packages/spatie/schema-org)
[![Package Version](https://img.shields.io/packagist/v/goldfinch/google-fields.svg?labelColor=333&color=F8C630&label=Version)](https://packagist.org/packages/spatie/schema-org)
[![Total Downloads](https://img.shields.io/packagist/dt/goldfinch/google-fields.svg?labelColor=333&color=F8C630&label=Downloads)](https://packagist.org/packages/spatie/schema-org)
[![License](https://img.shields.io/packagist/l/goldfinch/google-fields.svg?labelColor=333&color=F8C630&label=License)](https://packagist.org/packages/spatie/schema-org) 


Google Map and Google Place (Autocomplete) fields for Silverstripe


## Install

```
composer require goldfinch/google-fields
```

## Usage

#### Map component
![Screenshot](screenshots/map.png)

```php
use Goldfinch\GoogleFields\Forms\MapField;

private static $db = [
  'Map' => 'Map',
];

// ..

MapField::create('Map')

//

MapField::create('Map', 'Map')
  ->setSettings([
      'lng' => 168.7439017,
      'lat' => -45.0136784,
      'zoom' => 10,
  ])
  ->mapHideSearch()
  ->mapHideExtra()
  ->mapReadonly()
```
```html
<!-- template.ss -->

$Map
$Map.Link

$Map.Longitude
$Map.Latitude
$Map.Zoom
```

#### Place autocomplete component
![Screenshot](screenshots/place.png)

```php
use Goldfinch\GoogleFields\Forms\PlaceField;

private static $db = [
  'Place' => 'Place',
];

// ...

PlaceField::create('Place')

//

PlaceField::create('Place', 'Place')
  ->setSettings([
    'country' => 'ru',
  ])
  ->placeHidePreview()
```
```html
<!-- template.ss -->

$Place.Address
$Place.Data

$Place.Link

$Place.Subpremise
$Place.StreetNumber
$Place.StreetName
$Place.Suburb
$Place.Subarea
$Place.Region
$Place.District
$Place.Country
$Place.Postcode

$Place.PlaceName
$Place.Latitude
$Place.Longitude
```

## License

The MIT License (MIT)
