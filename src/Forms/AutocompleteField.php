<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\Autocomplete\Forms;

use InvalidArgumentException;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FormField;
use SilverStripe\View\Requirements;

/**
 * Form field with async autocomplete suggestions.
 *
 * Suggestions can be provided by either:
 * - a DataObject class + property, or
 * - a callback callable that receives the user query string.
 *
 * @package Clesson\Silverstripe\Autocomplete
 * @subpackage Forms
 */
class AutocompleteField extends FormField
{
    /** @var string|null DataObject class name used as source. */
    private ?string $sourceClass = null;

    /** @var string|null Property name used for suggestion values. */
    private ?string $sourceProperty = null;

    /** @var callable|string|array<int|string, mixed>|null Callback source. */
    private mixed $sourceCallback = null;

    /** @var int Maximum number of suggestions to return. */
    private int $limit = 10;

    /** @var int Minimum number of characters before request. */
    private int $minChars = 2;

    /**
     * Configures model-based suggestions.
     *
     * @param string $class DataObject class name
     * @param string $property Property that yields suggestion strings
     * @return static
     */
    public function setSourceModel(string $class, string $property): static
    {
        $this->sourceClass = $class;
        $this->sourceProperty = $property;
        $this->sourceCallback = null;

        return $this;
    }

    /**
     * Configures callback-based suggestions.
     *
     * Allowed callables: function names and Class::method callables.
     *
     * @param callable|string|array<int|string, mixed> $callback
     * @return static
     */
    public function setSourceCallback(mixed $callback): static
    {
        $this->sourceCallback = $callback;
        $this->sourceClass = null;
        $this->sourceProperty = null;

        return $this;
    }

    /**
     * Sets suggestion result limit.
     *
     * @param int $limit
     * @return static
     */
    public function setLimit(int $limit): static
    {
        $this->limit = max(1, $limit);

        return $this;
    }

    /**
     * Sets minimum input length before search requests.
     *
     * @param int $minChars
     * @return static
     */
    public function setMinChars(int $minChars): static
    {
        $this->minChars = max(1, $minChars);

        return $this;
    }

    /**
     * Returns this field type identifier.
     *
     * @return string
     */
    public function Type(): string
    {
        return 'autocomplete';
    }

    /**
     * Loads required frontend assets for this field.
     *
     * @param array $properties
     * @return mixed
     */
    public function Field($properties = []): mixed
    {
        Requirements::javascript('clesson-de/silverstripe-autocomplete:client/dist/autocomplete.js');
        Requirements::css('clesson-de/silverstripe-autocomplete:client/dist/autocomplete.css');

        return parent::Field($properties);
    }

    /**
     * Returns the search endpoint URL.
     *
     * @return string
     */
    public function getEndpointURL(): string
    {
        return Controller::join_links('autocomplete-api', 'search');
    }

    /**
     * Returns configured source type.
     *
     * @return string One of: model, callback
     */
    public function getSourceType(): string
    {
        return $this->sourceCallback ? 'callback' : 'model';
    }

    /**
     * Returns source model class.
     *
     * @return string
     */
    public function getSourceClass(): string
    {
        return (string) $this->sourceClass;
    }

    /**
     * Returns source model property.
     *
     * @return string
     */
    public function getSourceProperty(): string
    {
        return (string) $this->sourceProperty;
    }

    /**
     * Returns callback key registered in session.
     *
     * @return string
     */
    public function getCallbackKey(): string
    {
        if (!$this->sourceCallback) {
            return '';
        }

        $normalized = $this->normalizeCallback($this->sourceCallback);
        $session = Controller::curr()?->getRequest()?->getSession();

        if ($session === null) {
            return '';
        }

        $map = (array) $session->get('Clesson.Autocomplete.Callbacks');
        $key = sha1($this->getName() . '|' . $normalized);
        $map[$key] = $normalized;
        $session->set('Clesson.Autocomplete.Callbacks', $map);

        return $key;
    }

    /**
     * Returns configured suggestion limit.
     *
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Returns configured minimum character count.
     *
     * @return int
     */
    public function getMinChars(): int
    {
        return $this->minChars;
    }

    /**
     * Normalizes callback definitions to a stable string format.
     *
     * @param mixed $callback
     * @return string
     */
    private function normalizeCallback(mixed $callback): string
    {
        if (is_string($callback)) {
            return $callback;
        }

        if (is_array($callback) && count($callback) === 2) {
            $class = is_object($callback[0]) ? get_class($callback[0]) : (string) $callback[0];

            return $class . '::' . (string) $callback[1];
        }

        throw new InvalidArgumentException('Unsupported callback format for AutocompleteField.');
    }
}

