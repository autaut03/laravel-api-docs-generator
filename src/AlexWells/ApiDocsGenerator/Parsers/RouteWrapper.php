<?php

namespace AlexWells\ApiDocsGenerator\Parsers;

use AlexWells\ApiDocsGenerator\Exceptions\InvalidFormat;
use ReflectionClass;
use ReflectionParameter;
use Illuminate\Routing\Route;
use ReflectionFunctionAbstract;
use Illuminate\Support\Collection;
use Mpociot\Reflection\DocBlock\Tag;
use AlexWells\ApiDocsGenerator\Helpers;
use Illuminate\Foundation\Http\FormRequest;
use AlexWells\ApiDocsGenerator\Exceptions\InvalidTagFormat;
use AlexWells\ApiDocsGenerator\Exceptions\ClosureRouteException;

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
     * Parsed describe tag parser.
     *
     * @var DescribeParameterTagsParser
     */
    protected $describeTagsParser;

    /**
     * Parsed default tag parser.
     *
     * @var DefaultParameterTagsParser
     */
    protected $defaultTagsParser;

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
     * Returns original (laravel's) route object.
     *
     * @return Route
     */
    public function getOriginalRoute()
    {
        return $this->route;
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
            'resource' => $this->getResource(),
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
        // Get all path parameters from path, including ? symbols after them
        preg_match_all('/\{(.*?)\}/', $this->getUri(), $matches);

        return array_map(function ($pathParameter) {
            return (new PathParameterParser($pathParameter, ! $this->options['noTypeChecks'], $this))->parse();
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
                'default' => $this->getDefaultTagsParser()->get('query', $name),
                'rules' => $rules,
                'description' => $this->getDescribeTagsParser()->get('query', $name)
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
        if (! ($reflection = $this->getFormRequestClassReflection())) {
            return [];
        }

        return FormRequestRulesParser::withReflection($reflection)
            ->parse();
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
     * Returns route's resource.
     *
     * @return array
     */
    public function getResource()
    {
        return $this->getDocBlocks()
            ->map(function (DocBlockWrapper $docBlock) {
                $tag = $docBlock->getDocTag('resource');

                if (! $tag) {
                    return null;
                }

                $resource = $tag->getContent();

                if(! $resource) {
                    throw new InvalidFormat('Resource name not specified');
                }

                $resource = (new StringToArrayParser($resource))->parse();

                return $resource;
            })
            ->filter()
            ->first(null, ['Unclassified routes']);
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
                return (new ResponseParser($tag->getContent()))->parse();
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
     * Returns describe tags parser.
     *
     * @return DescribeParameterTagsParser
     */
    public function getDescribeTagsParser()
    {
        if (! $this->describeTagsParser) {
            return $this->describeTagsParser = new DescribeParameterTagsParser($this->getDocBlocksArray());
        }

        return $this->describeTagsParser;
    }

    /**
     * Returns default tags parser.
     *
     * @return DefaultParameterTagsParser
     */
    public function getDefaultTagsParser()
    {
        if (! $this->defaultTagsParser) {
            return $this->defaultTagsParser = new DefaultParameterTagsParser($this->getDocBlocksArray());
        }

        return $this->defaultTagsParser;
    }

    /**
     * Get all doc blocks.
     *
     * @return Collection|DocBlockWrapper[]
     */
    protected function getDocBlocks()
    {
        return collect($this->getDocBlocksArray());
    }

    /**
     * Get all doc blocks as array.
     *
     * @return DocBlockWrapper[]
     */
    protected function getDocBlocksArray()
    {
        return [$this->getMethodDocBlock(), $this->getControllerDocBlock()];
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
            $methodParameter = $this->getMethodParameterByType(FormRequest::class);

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
    protected function getMethodParameterByType($filter)
    {
        $formRequestParameters = $this->getMethodParametersByType($filter);

        if (empty($formRequestParameters)) {
            return null;
        }

        return array_first($formRequestParameters);
    }

    /**
     * Returns route method's parameters filtered by type.
     *
     * @param string $filter A parameter type to filter
     *
     * @return ReflectionParameter[]
     */
    protected function getMethodParametersByType($filter)
    {
        return $this->getMethodParameters(function (ReflectionParameter $parameter) use ($filter) {
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
     * Returns route method's parameters filtered by name (ignore case).
     *
     * TODO: get reflections into other class
     *
     * @param string $name
     *
     * @return ReflectionParameter
     */
    public function getMethodParameterByName($name)
    {
        return $this->getMethodParameter(function (ReflectionParameter $parameter) use ($name) {
            return strtolower($parameter->getName()) === strtolower($name);
        });
    }

    /**
     * Returns route method's parameter filtered by callable.
     *
     * @param callable $filter A callable returning bool
     *
     * @return ReflectionParameter
     */
    protected function getMethodParameter(callable $filter)
    {
        return array_first($this->getMethodParameters($filter));
    }

    /**
     * Returns route method's parameters filtered by callable.
     *
     * @param callable $filter A callable returning bool
     *
     * @return ReflectionParameter[]
     */
    protected function getMethodParameters(callable $filter = null)
    {
        $parameters = $this->getMethodReflection()->getParameters();

        if ($filter === null) {
            return $parameters;
        }

        return array_filter($parameters, $filter);
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
