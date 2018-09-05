<?php

namespace Nuwave\Lighthouse\Schema\Factories;

use Nuwave\Lighthouse\Support\Pipeline;
use Nuwave\Lighthouse\Schema\DirectiveRegistry;
use Nuwave\Lighthouse\Schema\Values\ArgumentValue;
use Nuwave\Lighthouse\Schema\Resolvers\NodeResolver;
use Nuwave\Lighthouse\Support\Contracts\ArgMiddleware;

class ArgumentFactory
{
    /** @var DirectiveRegistry */
    protected $directiveRegistry;

    /**
     * ArgumentFactory constructor.
     * @param DirectiveRegistry $directiveRegistry
     */
    public function __construct(DirectiveRegistry $directiveRegistry)
    {
        $this->directiveRegistry = $directiveRegistry;
    }

    /**
     * Convert argument definition to type.
     *
     * @param ArgumentValue $value
     *
     * @return array
     */
    public function handle(ArgumentValue $value): array
    {
        $value->setType(NodeResolver::resolve($value->getArg()->type));

        return $this->applyMiddleware($value)->getValue();
    }

    /**
     * Apply argument middleware.
     *
     * @param ArgumentValue $value
     *
     * @return ArgumentValue
     */
    protected function applyMiddleware(ArgumentValue $value): ArgumentValue
    {
        return app(Pipeline::class)
            ->send($value)
            ->through($this->directiveRegistry->argMiddleware($value->getArg()))
            ->via('handleArgument')
            ->always(function (ArgumentValue $value, ArgMiddleware $middleware) {
                return $value->setMiddlewareDirective($middleware->name());
            })
            ->then(function (ArgumentValue $value) {
                return $value;
            });
    }
}
