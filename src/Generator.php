<?php

namespace AjayMakwana\LaravelTestGenerator;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use ReflectionException;
use ReflectionMethod;

class Generator
{
    protected mixed $routeFilter;

    protected string $originalUri;

    protected string $action;

    protected array $config;

    protected TestCaseGenerator $testCaseGenerator;

    protected Formatter $formatter;

    protected bool $sync;

    /**
     * Initiate the global parameters
     */
    public function __construct(array $options)
    {
        $this->routeFilter = $options['filter'];
        $this->sync = $options['sync'];
        $this->testCaseGenerator = new  TestCaseGenerator();
        $this->formatter = new  Formatter($this->sync);
    }

    /**
     * Generate the route methods and write to the file
     * @throws ReflectionException
     */
    public function generate(): void
    {
        $cases = $this->getCases();

        $this->formatter->generate($cases);
    }

    /**
     * Get the route detail and generate the test cases
     * @throws ReflectionException
     */
    protected function getCases(): array
    {
        $cases = [];
        foreach ($this->getAppRoutes() as $route) {
            $this->originalUri = $uri = $this->getRouteUri($route);
            $uri = $this->strip_optional_char($uri);

            if ($this->routeFilter && !preg_match('/^' . preg_quote($this->routeFilter, '/') . '/', $uri)) {
                continue;
            }

            $action = $route->getAction('uses');
            $methods = $route->methods();
            $actionName = $this->getActionName($route->getActionName());

            $controllerName = $this->getControllerName($route->getActionName());

            foreach ($methods as $method) {
                $method = strtoupper($method);

                if ($method == 'HEAD') continue;

                $rules = $this->getFormRules($action);
                if (empty($rules)) {
                    $rules = [];
                }
                $case = $this->testCaseGenerator->generate($rules);
                $hasAuth = $this->isAuthorizationExist($route->middleware());

                list('key' => $caseKey, 'value' => $caseValue) = $this->formatter->format(
                    case: $case,
                    url: $uri,
                    method: $method,
                    controllerName: $controllerName,
                    actionName: $actionName,
                    auth: $hasAuth
                );

                $cases[$caseKey] = $caseValue;
            }
        }

        return $cases;
    }

    /**
     * Check authorization middleware is exist
     */
    protected function isAuthorizationExist(array $middlewares): array
    {
        $hasAuth = array_filter($middlewares, function ($var) {
            return (strpos($var, 'auth') > -1);
        });

        return $hasAuth;
    }

    /**
     * Replace the optional params from the URL
     */
    protected function strip_optional_char(string $uri): string|array
    {
        return str_replace('?', '', $uri);
    }

    /**
     * Get the routes of the application
     *
     * @return array|Route[]
     */
    protected function getAppRoutes(): mixed
    {
        return app('router')->getRoutes();
    }


    protected function getRouteUri(Route $route): string
    {
        $uri = $route->uri();

        if (!str_starts_with($uri, '/')) {
            $uri = '/' . $uri;
        }

        return $uri;
    }

    /**
     * @throws ReflectionException
     */
    protected function getFormRules(mixed $action): ?array
    {
        if (!is_string($action)) {
            return null;
        }

        $parsedAction = Str::parseCallback($action);

        $reflector = (new ReflectionMethod($parsedAction[0], $parsedAction[1]));
        $parameters = $reflector->getParameters();

        foreach ($parameters as $parameter) {
            $class = optional($parameter->getType())->getName();

            if (is_subclass_of($class, FormRequest::class)) {
                return (new $class)->rules();
            }
        }

        return null;
    }


    protected function getControllerName(string $controller): string
    {
        $namespaceReplaced = substr($controller, strrpos($controller, '\\') + 1);
        $actionNameReplaced = substr($namespaceReplaced, 0, strpos($namespaceReplaced, '@'));
        $controllerReplaced = str_replace('Controller', '', $actionNameReplaced);
        $controllerNameArray = preg_split('/(?=[A-Z])/', $controllerReplaced);
        $controllerName = trim(implode('', $controllerNameArray));

        return $controllerName;
    }

    protected function getActionName(string $actionName): string
    {
        $actionNameSubString = substr($actionName, strpos($actionName, '@') + 1);
        $actionNameArray = preg_split('/(?=[A-Z])/', ucfirst($actionNameSubString));
        $actionName = trim(implode('', $actionNameArray));

        return $actionName;
    }
}
