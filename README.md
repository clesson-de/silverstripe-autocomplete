# silverstripe-autocomplete

A Silverstripe module providing `AutocompleteField`, a form field with async autocomplete suggestions.

## Features

- Suggestion source via **DataObject class + property**
- Suggestion source via **callback callable**
- Lightweight vanilla JS dropdown UI

## Requirements

- PHP requirement: `^8.1`
- SilverStripe 6: `silverstripe/framework ^6` and `silverstripe/admin ^3`

These are the constraints declared by this module; dependencies may impose
additional requirements. The CodeGuard development dependency requires PHP 8.2
or newer.

## Installation

```bash
composer require clesson-de/silverstripe-autocomplete
composer vendor-expose
```

## Usage

```php
use Clesson\Silverstripe\Autocomplete\Forms\AutocompleteField;

// 1) Model + property source
$cityField = AutocompleteField::create('City', 'City')
    ->setSourceModel(\App\Models\Place::class, 'City')
    ->setLimit(8)
    ->setMinChars(2);

// 2) Callback source
$streetField = AutocompleteField::create('Street', 'Street')
    ->setSourceCallback([\App\Services\AddressService::class, 'searchStreetNames'])
    ->setLimit(10);

$fields->addFieldsToTab('Root.Main', [$cityField, $streetField]);
```

### Configuration

Configure each field with `setSourceModel()` for a DataObject class and property
or `setSourceCallback()` for a callback. Selecting either source replaces the
other. `setLimit()` sets the maximum number of suggestions (default: 10), and
`setMinChars()` sets the minimum input length before a request (default: 2).
Both values have a minimum of 1.

### Callback contract

Your callback receives the entered query string and must return an array of strings.

```php
public static function searchStreetNames(string $query): array
{
    if ($query === '') {
        return [];
    }

    return [
        'Main Street',
        'Market Street',
    ];
}
```

## API endpoint

`GET /autocomplete-api/search`

Request parameters are set automatically by `AutocompleteField`.

## License

BSD-3-Clause
