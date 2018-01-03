<?php

namespace AlexWells\ApiDocsGenerator\Parsers;

use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Foundation\Http\FormRequest;
use ReflectionClass;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

class FormRequestRulesParser
{
    /**
     * Form request class name string.
     *
     * @var string
     */
    protected $className;

    /**
     * Reflection of FormRequest class.
     *
     * @var ReflectionClass
     */
    protected $reflection;

    /**
     * FormRequest instance.
     *
     * @var FormRequest
     */
    protected $formRequestInstance;

    /**
     * Set FormRequest class name.
     *
     * @param string $className
     * @return $this
     */
    public function setClassName(string $className)
    {
        $this->className = $className;

        return $this;
    }

    /**
     * Set reflection class.
     *
     * @param ReflectionClass $reflection
     * @return $this
     */
    public function setReflection(ReflectionClass $reflection)
    {
        $this->reflection = $reflection;

        return $this;
    }

    /**
     * Set FormRequest class instance.
     *
     * @param FormRequest $instance
     * @return $this
     */
    public function setFormRequestInstance(FormRequest $instance)
    {
        $this->formRequestInstance = $instance;

        return $this;
    }

    /**
     * Create instance from ReflectionClass.
     *
     * @param ReflectionClass $reflection
     * @return $this
     */
    public static function withReflection(ReflectionClass $reflection)
    {
        return (new static())
            ->setClassName($reflection->getName())
            ->setReflection($reflection);
    }

    /**
     * Create instance from class name.
     *
     * @param string $className
     * @return $this
     */
    public static function withClassName(string $className)
    {
        return (new static())
            ->setClassName($className)
            ->setReflection(new ReflectionClass($className));
    }

    /**
     * Create instance from FormRequest instance.
     *
     * @param FormRequest $instance
     * @return $this
     */
    public static function withInstance(FormRequest $instance)
    {
        return static::withClassName(get_class($instance))
            ->setFormRequestInstance($instance);
    }

    /**
     * Parse the rules.
     *
     * @return array
     */
    public function parse()
    {
        $instance = $this->getFormRequestInstance();

        if(method_exists($instance, 'validator')) {
            $rules = $this->parseRulesFromValidator();
        } elseif(method_exists($instance, 'rules')) {
            $rules = $this->parseRulesFromMethod();
        } else {
            return [];
        }

        return $this->formatRules($rules);
    }

    /**
     * Format the rules into single format.
     *
     * @return array
     */
    protected static function formatRules(array $rules)
    {
        // This was copied from Laravel
        return array_map(function ($rule) {
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
    }

    /**
     * Parse rules from FormRequest's `validate` method.
     *
     * @return array
     */
    protected function parseRulesFromValidator()
    {
        // First we call needed method with needed container
        $factory = app()->make(ValidationFactory::class);
        $validator = app()->call([$this->getFormRequestInstance(), 'validator'], [$factory]);

        // After that we get initialRules property (since plain `rules` prop doesn't contain array rules)
        // and make it public so that later we can get it's value
        $property = (new ReflectionClass($validator))->getProperty('initialRules');
        $property->setAccessible(true);

        return $property->getValue($validator);
    }

    /**
     * Parse rules from FormRequest's `rules` method.
     *
     * @return array
     */
    protected function parseRulesFromMethod()
    {
        return app()->call([$this->getFormRequestInstance(), 'rules']);
    }

    /**
     * Return FormRequest class instance.
     *
     * @return FormRequest
     */
    protected function getFormRequestInstance()
    {
        if(! $this->formRequestInstance) {
            return $this->formRequestInstance = $this->getNewFormRequestInstance();
        }

        return $this->formRequestInstance;
    }

    /**
     * Get new FormRequest class instance with container but without calling `validate` method.
     *
     * @return FormRequest
     */
    protected function getNewFormRequestInstance()
    {
        // First we get container instance and it's reflection
        $container = app();
        $containerReflection = new ReflectionClass($container);

        // Then we get `afterResolvingCallbacks` property of container,
        // make it accessible (public) and store it's value into temp var
        $property = $containerReflection->getProperty('afterResolvingCallbacks');
        $property->setAccessible(true);
        $originalValue = $property->getValue($container);

        // After that we get it's value once again into new variable and do the most important part:
        // we set an empty array for `ValidatesWhenResolved` interface, which stops Laravel's
        // container from calling `validate` method right after creating FormRequest class,
        // which uses `ValidatesWhenResolvedTrait`
        $modified = $property->getValue($container);
        $modified[ValidatesWhenResolved::class] = [];
        $property->setValue($container, $modified);

        // Then we get a new instance of FormRequest using our reflection,
        // so that it doesn't throw a ValidationException right away
        /** @var FormRequest $formRequest */
        $formRequest = $containerReflection->getMethod('make')->invoke($container, $this->className);

        // Just in case we set original value back
        $property->setValue($container, $originalValue);

        // Finally, we return created instance
        return $formRequest;
    }
}