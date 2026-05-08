# silverstripe-autocomplete

A Silverstripe module providing `AutocompleteField`, a form field with async autocomplete suggestions.

## Features

- Suggestion source via **DataObject class + property**
- Suggestion source via **callback callable**
- Lightweight vanilla JS dropdown UI

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
    ->setSourceModel(\App\Models\Address::class, 'City')
    ->setLimit(8)
    ->setMinChars(2);

// 2) Callback source
$streetField = AutocompleteField::create('Street', 'Street')
    ->setSourceCallback([\App\Services\AddressService::class, 'searchStreetNames'])
    ->setLimit(10);

$fields->addFieldsToTab('Root.Main', [$cityField, $streetField]);
```

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

