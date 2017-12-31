<?php

namespace AlexWells\ApiDocsGenerator\Parser;

use AlexWells\ApiDocsGenerator\Exceptions\NoTypeSpecifiedException;
use Illuminate\Database\Eloquent\Model;
use ReflectionParameter;

class PathParameterParser
{
    /**
     * Path parameter name.
     *
     * @var string
     */
    protected $name;

    /**
     * Should method parameters without type throw an exception.
     *
     * @var bool
     */
    protected $typeChecksEnabled;

    /**
     * A route wrapper object.
     *
     * @var RouteWrapper
     */
    protected $route;

    /**
     * Parsed parameter reflection.
     *
     * @var
     */
    protected $parameterReflection;

    /**
     * PathParameterParser constructor.
     * @param RouteWrapper $route
     */
    public function __construct($name, $typeChecksEnabled, RouteWrapper $route)
    {
        $this->name = $name;
        $this->typeChecksEnabled = $typeChecksEnabled;
        $this->route = $route;
    }

    /**
     * Parse parameter
     *
     * @return array
     */
    public function parse()
    {
        return [
            'name' => $this->getName(),
            'required' => $this->isRequired(),
            'type' => $this->getType(),
            'default' => $this->getDefaultValue(),
            'regex' => $this->getRegex(),
            'description' => $this->getDescription()
        ];
    }

    public function getName()
    {
        return rtrim($this->name, '?');
    }

    /**
     * Checks if the parameter is required.
     *
     * @return bool
     */
    public function isRequired()
    {
        return ! ends_with($this->name, '?');
    }

    /**
     * Returns parameter's type.
     * @return string
     * @throws NoTypeSpecifiedException
     */
    public function getType()
    {
        if(! ($reflection = $this->getParameterReflection())) {
            return null;
        }

        if(! ($parameterType = $reflection->getType())) {
            if($this->typeChecksEnabled) {
                throw new NoTypeSpecifiedException("No type specified for parameter " . $this->getName());
            }

            return null;
        }

        if ($parameterType->isBuiltin()) {
            return strval($parameterType);
        }

        if (! ($parameterClass = $reflection->getClass())) {
            return null;
        }

        $type = $parameterClass->getShortName();

        if ($parameterClass->isSubclassOf(Model::class)) {
            //if (empty($description)) {
            //    $description = "`$type` id"; // TODO
            //}
            $type = 'model_id';
        }

        return $type;
    }

    /**
     * Returns parameter's default value.
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        $default = $this->route->getDefaultTagsParser()->get('path', $this->getName());

        if(!is_null($default)) {
            return $default;
        }

        if(! ($reflection = $this->getParameterReflection()) || ! $reflection->isDefaultValueAvailable()) {
            return null;
        }

        return strval($reflection->getDefaultValue());
    }

    /**
     * Returns parameter's regex pattern (from ->where() calls).
     *
     * @return string|null
     */
    public function getRegex()
    {
        return $this->route->getOriginalRoute()->wheres[$this->getName()] ?? null;
    }

    /**
     * Returns parameter's description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->route->getDescribeTagsParser()->get('path', $this->getName());
    }

    /**
     * Returns parameter's reflection.
     *
     * @return ReflectionParameter
     */
    protected function getParameterReflection()
    {
        if (! $this->parameterReflection) {
            return $this->parameterReflection = $this->route->getMethodParameterByName($this->getName());
        }

        return $this->parameterReflection;
    }
}