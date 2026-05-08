<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\Autocomplete\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;

/**
 * API controller serving autocomplete suggestions.
 *
 * @package Clesson\Silverstripe\Autocomplete
 * @subpackage Controllers
 */
class AutocompleteController extends Controller
{
    /**
     * @config
     */
    private static array $allowed_actions = [
        'search',
    ];

    /**
     * @config
     */
    private static array $url_handlers = [
        'search' => 'search',
    ];

    /**
     * Restricts API access to authenticated users.
     *
     * @return void
     */
    protected function init(): void
    {
        parent::init();

        if (!Security::getCurrentUser()) {
            $this->httpError(403, _t(__CLASS__ . '.NOT_LOGGED_IN', 'You must be logged in.'));
        }
    }

    /**
     * Returns matching autocomplete suggestions as JSON.
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function search(HTTPRequest $request): HTTPResponse
    {
        $query = trim((string) $request->getVar('q'));
        $limit = max(1, (int) ($request->getVar('limit') ?: 10));
        $type = (string) $request->getVar('sourceType');

        if ($query === '') {
            return $this->jsonResponse(['results' => []]);
        }

        if ($type === 'model') {
            return $this->jsonResponse(['results' => $this->searchByModel($request, $query, $limit)]);
        }

        if ($type === 'callback') {
            return $this->jsonResponse(['results' => $this->searchByCallback($request, $query, $limit)]);
        }

        return $this->jsonResponse([
            'error' => _t(__CLASS__ . '.INVALID_SOURCE', 'Invalid source.'),
        ], 400);
    }

    /**
     * Resolves suggestions from a DataObject class + property.
     *
     * @param HTTPRequest $request
     * @param string $query
     * @param int $limit
     * @return array<int, string>
     */
    private function searchByModel(HTTPRequest $request, string $query, int $limit): array
    {
        $class = (string) $request->getVar('sourceClass');
        $property = (string) $request->getVar('sourceProperty');

        if (!$this->isValidModelSource($class, $property)) {
            return [];
        }

        $singleton = DataObject::singleton($class);
        $dbFields = (array) $singleton->config()->get('db');

        if (isset($dbFields[$property])) {
            return $this->searchDbProperty($class, $property, $query, $limit);
        }

        return $this->searchComputedProperty($class, $property, $query, $limit);
    }

    /**
     * Resolves suggestions using a callback key from session.
     *
     * @param HTTPRequest $request
     * @param string $query
     * @param int $limit
     * @return array<int, string>
     */
    private function searchByCallback(HTTPRequest $request, string $query, int $limit): array
    {
        $callback = $this->resolveSessionCallback($request);

        if ($callback === null) {
            return [];
        }

        $result = call_user_func($callback, $query);

        return $this->normalizeResultArray(is_array($result) ? $result : [], $limit);
    }

    /**
     * Returns whether model source parameters are valid.
     *
     * @param string $class
     * @param string $property
     * @return bool
     */
    private function isValidModelSource(string $class, string $property): bool
    {
        if ($class === '' || $property === '') {
            return false;
        }

        if (!class_exists($class) || !is_subclass_of($class, DataObject::class)) {
            return false;
        }

        return (bool) preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $property);
    }

    /**
     * Searches a DB-backed property with PartialMatch filtering.
     *
     * @param class-string<DataObject> $class
     * @param string $property
     * @param string $query
     * @param int $limit
     * @return array<int, string>
     */
    private function searchDbProperty(string $class, string $property, string $query, int $limit): array
    {
        $records = $class::get()->filter([$property . ':PartialMatch' => $query])->limit($limit);

        return $this->normalizeResultArray($records->column($property), $limit);
    }

    /**
     * Searches a computed/non-db property by iterating records.
     *
     * @param class-string<DataObject> $class
     * @param string $property
     * @param string $query
     * @param int $limit
     * @return array<int, string>
     */
    private function searchComputedProperty(string $class, string $property, string $query, int $limit): array
    {
        $matches = [];
        $records = $class::get()->limit(200);

        foreach ($records as $record) {
            $value = trim((string) $record->{$property});

            if ($value === '' || stripos($value, $query) === false) {
                continue;
            }

            $matches[] = $value;

            if (count($matches) >= $limit) {
                break;
            }
        }

        return $this->normalizeResultArray($matches, $limit);
    }

    /**
     * Resolves a callback from session storage.
     *
     * @param HTTPRequest $request
     * @return callable|null
     */
    private function resolveSessionCallback(HTTPRequest $request): callable|null
    {
        $key = (string) $request->getVar('callbackKey');
        $map = (array) $request->getSession()->get('Clesson.Autocomplete.Callbacks');
        $normalized = (string) ($map[$key] ?? '');

        if ($normalized === '') {
            return null;
        }

        if (str_contains($normalized, '::')) {
            [$class, $method] = explode('::', $normalized, 2);
            return is_callable([$class, $method]) ? [$class, $method] : null;
        }

        return is_callable($normalized) ? $normalized : null;
    }

    /**
     * Normalizes suggestion lists to unique non-empty strings.
     *
     * @param array<int, mixed> $items
     * @param int $limit
     * @return array<int, string>
     */
    private function normalizeResultArray(array $items, int $limit): array
    {
        $strings = [];

        foreach ($items as $item) {
            $value = trim((string) $item);

            if ($value !== '') {
                $strings[] = $value;
            }
        }

        return array_slice(array_values(array_unique($strings)), 0, $limit);
    }

    /**
     * Returns a JSON response.
     *
     * @param array<string, mixed> $data
     * @param int $status
     * @return HTTPResponse
     */
    private function jsonResponse(array $data, int $status = 200): HTTPResponse
    {
        $response = HTTPResponse::create();
        $response->setStatusCode($status);
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody((string) json_encode($data, JSON_UNESCAPED_UNICODE));

        return $response;
    }
}

