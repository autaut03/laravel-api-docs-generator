<?php

namespace AlexWells\ApiDocsGenerator\Parser;

use AlexWells\ApiDocsGenerator\Helpers;
use ReflectionClass;
use ReflectionParameter;
use Illuminate\Routing\Route;
use ReflectionFunctionAbstract;
use Illuminate\Support\Collection;
use Mpociot\Reflection\DocBlock\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use AlexWells\ApiDocsGenerator\Exceptions\InvalidTagFormat;
use AlexWells\ApiDocsGenerator\Exceptions\ClosureRouteException;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use AlexWells\ApiDocsGenerator\Exceptions\NoTypeSpecifiedException;

class RouteWrapper
{
    /**
     * Original route object.
     *
     * @var Route
     */
    protected $route;

    /**
     * Injected array of options from instance.
     *
     * @var array
     */
    protected $options;

    /**
     * Parsed FormRequest's reflection.
     *
     * @var ReflectionClass
     */
    protected $parsedFormRequest;

    /**
     * Parsed doc block for controller.
     *
     * @var DocBlockWrapper
     */
    protected $controllerDockBlock;

    /**
     * Parsed doc block for method.
     *
     * @var DocBlockWrapper
     */
    protected $methodDocBlock;

    /**
     * Parsed parameter descriptions.
     *
     * @var array
     */
    protected $parameterDescriptions;

    /**
     * Parameter types.
     */
    protected const PARAMETER_TYPES = ['query', 'path'];

    /**
     * RouteWrapper constructor.
     *
     * @param Route $route
     * @param array $options
     */
    public function __construct($route, $options)
    {
        $this->route = $route;
        $this->options = $options;
    }

    /**
     * Parse the route and return summary information for it.
     *
     * @return array
     */
    public function getSummary()
    {
        return [
            'id' => $this->getSignature(),
            'resource' => $this->getResourceName(),
            'uri' => $this->getUri(),
            'methods' => $this->getMethods(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'parameters' => [
                'path' => $this->getPathParameters(),
                'query' => $this->getQueryParameters()
            ],
            'responses' => $this->getResponses()
        ];
    }

    /**
     * Returns route's unique signature.
     *
     * @return string
     */
    public function getSignature()
    {
        return md5($this->getUri() . ':' . implode(',', $this->getMethods()));
    }

    /**
     * Returns route's name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->route->getName();
    }

    /**
     * Returns route's HTTP methods.
     *
     * @return array
     */
    public function getMethods()
    {
        if (method_exists($this->route, 'getMethods')) {
            $methods = $this->route->getMethods();
        } else {
            $methods = $this->route->methods();
        }

        return array_except($methods, 'HEAD');
    }

    /**
     * Returns route's URI.
     *
     * @return string
     */
    public function getUri()
    {
        if (method_exists($this->route, 'getUri')) {
            return $this->route->getUri();
        }

        return $this->route->uri();
    }

    /**
     * Parse the action and return it.
     *
     * @return string[2]
     */
    protected function parseAction()
    {
        return explode('@', $this->getAction(), 2);
    }

    /**
     * Returns route's action string.
     *
     * @throws ClosureRouteException
     *
     * @return string
     */
    public function getAction()
    {
        if (! $this->isSupported()) {
            throw new ClosureRouteException('Closure callbacks are not supported. Please use a controller method.');
        }

        return $this->getActionSafe();
    }

    /**
     * Returns route's action string safe (without any exceptions).
     *
     * @return string
     */
    public function getActionSafe()
    {
        return $this->route->getActionName();
    }

    /**
     * Checks if the route is supported.
     *
     * @return bool
     */
    public function isSupported()
    {
        return isset($this->route->getAction()['controller']);
    }

    /**
     * Checks if it matches any of the masks.
     *
     * @param array $masks
     *
     * @return bool
     */
    public function matchesAnyMask($masks)
    {
        return collect($masks)
            ->contains(function ($mask) {
                return str_is($mask, $this->getUri()) || str_is($mask, $this->getName());
            });
    }

    /**
     * Parses path parameters and returns them.
     *
     * @return array
     */
    public function getPathParameters()
    {
        preg_match_all('/\{(.*?)\}/', $this->getUri(), $matches);
        $methodParameters = $this->getMethodReflection()->getParameters();

        return array_map(function ($pathParameter) use ($methodParameters) {
            $name = trim($pathParameter, '?');
            /** @var ReflectionParameter $methodParameter */
            $methodParameter = array_first($methodParameters, function (ReflectionParameter $methodParameter) use ($name) {
                return strtolower($methodParameter->getName()) === strtolower($name);
            });

            $type = null;
            $default = null;
            $description = $this->getParameterDescription('path', $name);

            if ($methodParameter) {
                $parameterType = $methodParameter->getType();
                if (! $parameterType && ! $this->options['noTypeChecks']) {
                    throw new NoTypeSpecifiedException("No type specified for parameter `$name`");
                }

                if ($parameterType) {
                    if ($parameterType->isBuiltin()) {
                        $type = strval($parameterType);
                    } elseif ($parameterClass = $methodParameter->getClass()) {
                        $type = $parameterClass->getShortName();

                        if ($parameterClass->isSubclassOf(Model::class)) {
                            if (empty($description)) {
                                $description = "`$type` id";
                            }
                            $type = 'model_id';
                        }
                    }
                }

                if ($methodParameter->isOptional()) {
                    $default = $methodParameter->getDefaultValue();
                }
            }

            $regex = $this->route->wheres[$name] ?? null;

            // TODO remake
            return [
                'name' => $name,
                'required' => ($name === $pathParameter), // trimmed nothing
                'type' => $type,
                'default' => $default,
                'regex' => $regex,
                'description' => $description
            ];
        }, $matches[1]);
    }

    /**
     * Parses validation rules and converts them into an array of parameters.
     *
     * @return array
     */
    public function getQueryParameters()
    {
        $params = [];

        foreach ($this->getQueryValidationRules() as $name => $rules) {
            $params[] = [
                'name' => $name,
                'default' => '', // TODO: parse defaults from tag @default
                'rules' => $rules,
                'description' => $this->getParameterDescription('query', $name)
            ];
        }

        return $params;
    }

    /**
     * Return an array of query validation rules.
     *
     * @return array
     */
    protected function getQueryValidationRules()
    {
        if (! ($formRequestReflection = $this->getFormRequestClassReflection())) {
            return [];
        }

        $className = $formRequestReflection->getName();

        /*
         * TODO: REFACTOR BEGIN
         */
        $container = app();
        $containerReflection = new ReflectionClass($container);

        $property = $containerReflection->getProperty('afterResolvingCallbacks');
        $property->setAccessible(true);
        $originalValue = $property->getValue($container);

        $modified = $property->getValue($container);
        $modified[ValidatesWhenResolved::class] = [];

        $property->setValue($container, $modified);

        /** @var FormRequest $formRequest */
        $formRequest = $containerReflection->getMethod('make')->invoke($container, $className);

        $property->setValue($container, $originalValue);
        /*
         * TODO: REFACTOR END
         */

        if ($formRequestReflection->hasMethod('validator')) {
            $factory = app()->make(ValidationFactory::class);
            $validator = app()->call([$formRequest, 'validator'], [$factory]);

            $property = (new ReflectionClass($validator))->getProperty('initialRules');
            $property->setAccessible(true);

            $rules = $property->getValue($validator);
        } else {
            $rules = app()->call([$formRequest, 'rules']);
        }

        $rules = array_map(function ($rule) {
            if (is_string($rule)) {
                return explode('|', $rule);
            } elseif (is_object($rule)) {
                return [strval($rule)];
            } else {
                return array_map(function ($rule) {
                    return is_object($rule) ? strval($rule) : $rule;
                }, $rule);
            }
        }, $rules);

        return $rules;
    }

    /**
     * Returns parameter description.
     *
     * @param $in
     * @param $name
     *
     * @return string
     */
    public function getParameterDescription($in, $name)
    {
        return array_get($this->getParameterDescriptions(), "$in.$name", '');
    }

    /**
     * Returns parameter descriptions.
     *
     * @return array
     */
    public function getParameterDescriptions()
    {
        if (! $this->parameterDescriptions) {
            return $this->parameterDescriptions = $this->parseParameterDescriptions();
        }

        return $this->parameterDescriptions;
    }

    /**
     * Parses parameter descriptions.
     *
     * @throws InvalidTagFormat
     *
     * @return array
     */
    protected function parseParameterDescriptions()
    {
        $descriptions = [];

        foreach ($this->getDocBlocks() as $docBlock) {
            foreach ($docBlock->getDocTags('describe') as $tag) {
                $content = $tag->getContent();

                $parts = preg_split('/(\s+)/Su', $content, 3, PREG_SPLIT_DELIM_CAPTURE);

                if (! $parts || count($parts) !== 5) {
                    throw new InvalidTagFormat(`Not enough arguments passed for {$tag->getName()}`);
                }

                $in = $parts[0];

                if (! in_array($in, self::PARAMETER_TYPES)) {
                    throw new InvalidTagFormat(`Invalid parameter location specified for {$tag->getName()}`);
                }

                $name = $parts[2];
                $description = $parts[4];

                $descriptions[$in][$name] = $description;
            }
        }

        return $descriptions;
    }

    /**
     * Returns route's title (defaults to method name).
     *
     * @return string
     */
    public function getTitle()
    {
        if($title = $this->getMethodDocBlock()->getShortDescription()) {
            return $title;
        }

        $title = $this->getMethodReflection()->getName();

        return Helpers::functionNameToText($title);
    }

    /**
     * Returns route's description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        $description = $this->getMethodDocBlock()->getLongDescription()->getContents();

        return Helpers::clearNewlines($description);
    }

    /**
     * Returns route's resource name.
     *
     * @return string
     */
    public function getResourceName()
    {
        return $this->getDocBlocks()
            ->map(function (DocBlockWrapper $docBlock) {
                $tag = $docBlock->getDocTag('resource');

                if (! $tag) {
                    return null;
                }

                $resource = $tag->getContent();

                if(! $resource) {
                    throw new InvalidTagFormat('Resource name not specified');
                }

                return $resource;
            })
            ->filter()
            ->first(null, 'Unclassified routes');
    }

    /**
     * Returns all route's responses.
     *
     * @return array
     */
    public function getResponses()
    {
        return $this->getMethodDocBlock()
            ->getDocTags('response')
            ->map(function (Tag $tag) {
                $content = $tag->getContent();
                $content = Helpers::clearNewlines($content);

                if(! $content) {
                    return null;
                }

                // TODO: extract into a class and each "replace" into method like `replaceWhat`

                $cutOffInQuotes = "\s*(?=([^\"]*\"[^\"]*\")*[^\"]*$)";

                // TODO: allow to use single quotes (and replace them automatically)
                // replace `int[]` with `[ :: int ]`
                $content = preg_replace("/(\w+)\[\]$cutOffInQuotes/", '[ :: $1 ]', $content);
                // replace `nested: {}` with `"nested": {}`
                $content = preg_replace("/(\w+)\s*:[^:]$cutOffInQuotes/", '"$1": ', $content);
                // replace `year :: int` with `"year": {"$ref": "int"}`
                $content = preg_replace("/(\w+)\s*::\s*(\w+)$cutOffInQuotes/", '"$1": {"$ref": "$2"}', $content);
                // replace `:: int` with `{"$ref": "int"}`
                $content = preg_replace("/\s*::\s*(\w+)$cutOffInQuotes/", '{"$ref": "$1"}', $content);
                // replace `:: {}` with `{"$ref": {}}`
                $content = preg_replace("/::\s*{(.*)}$cutOffInQuotes/", '{"$ref": {$1}}', $content);

                $content = json_decode($content, true);

                if(! $content) {
                    throw new InvalidTagFormat('Response tag format is invalid, JSON decode error: ' . json_last_error_msg());
                }

                return $content;
            })
            ->filter()
            ->toArray();
    }

    /**
     * Checks if the route is hidden from docs by annotation.
     *
     * @return bool
     */
    public function isHiddenFromDocs()
    {
        return $this->getDocBlocks()
            ->contains(function (DocBlockWrapper $docBlock) {
                return $docBlock->hasDocTag('docsHide');
            });
    }

    /**
     * Get all doc blocks.
     *
     * @return Collection|DocBlockWrapper[]
     */
    protected function getDocBlocks()
    {
        return collect([$this->getMethodDocBlock(), $this->getControllerDocBlock()]);
    }

    /**
     * Returns DocBlock for route method.
     *
     * @return DocBlockWrapper
     */
    protected function getMethodDocBlock()
    {
        if (! $this->methodDocBlock) {
            return $this->methodDocBlock = new DocBlockWrapper($this->getMethodReflection());
        }

        return $this->methodDocBlock;
    }

    /**
     * Returns DocBlock for the controller.
     *
     * @return DocBlockWrapper
     */
    protected function getControllerDocBlock()
    {
        if (! $this->controllerDockBlock) {
            return $this->controllerDockBlock = new DocBlockWrapper($this->getControllerReflection());
        }

        return $this->controllerDockBlock;
    }

    /**
     * Returns route's FormRequest reflection if exists.
     *
     * @return ReflectionClass
     */
    protected function getFormRequestClassReflection()
    {
        if (! $this->parsedFormRequest) {
            $methodParameter = $this->getMethodParameter(FormRequest::class);

            if (! $methodParameter) {
                return null;
            }

            return $this->parsedFormRequest = $methodParameter->getClass();
        }

        return $this->parsedFormRequest;
    }

    /**
     * Returns method parameter by type (single).
     *
     * @param string $filter
     *
     * @return ReflectionParameter
     */
    protected function getMethodParameter($filter = null)
    {
        $formRequestParameters = $this->getMethodParameters($filter);

        if (empty($formRequestParameters)) {
            return null;
        }

        return array_first($formRequestParameters);
    }

    /**
     * Returns route method's parameters filtered by type.
     *
     * @param string $filter a parameter type to filter
     *
     * @return ReflectionParameter[]
     */
    protected function getMethodParameters($filter = null)
    {
        $parameters = $this->getMethodReflection()->getParameters();

        if ($filter === null) {
            return $parameters;
        }

        return array_filter($parameters, function (ReflectionParameter $parameter) use ($filter) {
            if (! ($type = $parameter->getType())) {
                return false;
            }

            if ($type->isBuiltin()) {
                return strval($type) === $filter;
            }

            return ($class = $parameter->getClass()) && $class->isSubclassOf($filter);
        });
    }

    /**
     * Returns route method's reflection.
     *
     * @return ReflectionFunctionAbstract
     */
    protected function getMethodReflection()
    {
        return $this->getControllerReflection()->getMethod($this->parseAction()[1]);
    }

    /**
     * Returns controller class reflection.
     *
     * @return ReflectionClass
     */
    protected function getControllerReflection()
    {
        return new ReflectionClass($this->parseAction()[0]);
    }
}
