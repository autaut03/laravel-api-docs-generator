<?php

namespace AlexWells\ApiDocsGenerator\Postman;

use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;

class CollectionGenerator
{
    /**
     * @var Collection
     */
    private $routeGroups;

    /**
     * CollectionGenerator constructor.
     *
     * @param Collection $routeGroups
     */
    public function __construct(Collection $routeGroups)
    {
        $this->routeGroups = $routeGroups;
    }

    public function getCollection()
    {
        $collection = [
            'variables' => [],
            'info' => [
                'name' => '',
                '_postman_id' => Uuid::uuid4()->toString(),
                'description' => '',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.0.0/collection.json',
            ],
            'item' => $this->routeGroups->map(function ($routes, $groupName) {
                return [
                    'name' => $groupName,
                    'description' => '',
                    'item' => $routes->map(function ($route) {
                        return [
                            'name' => $route['title'] != '' ? $route['title'] : url($route['uri']),
                            'request' => [
                                'url' => url($route['uri']),
                                'method' => $route['methods'][0],
                                'body' => [
                                    'mode' => 'formdata',
                                    'formdata' => collect($route['parameters']['query'])->map(function ($parameter) {
                                        return [
                                            'key' => $parameter['name'],
                                            'value' => '',
                                            'type' => collect($parameter['rules'])->contains(function($rule) {
                                                return in_array($rule, ['file', 'image']);
                                                // TODO: advanced mime checks
                                            }) ? 'file' : 'text', // this check is fine for now
                                            'enabled' => true,
                                        ];
                                    })->values()->toArray(),
                                ],
                                'description' => $route['description'],
                                'response' => [],
                            ],
                        ];
                    })->toArray(),
                ];
            })->values()->toArray(),
        ];

        return json_encode($collection);
    }
}
