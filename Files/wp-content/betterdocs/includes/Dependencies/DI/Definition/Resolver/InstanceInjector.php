<?php
// phpcs:ignoreFile -- Bundled third-party (Mozart) dependency; exempt from plugin coding standards.

declare(strict_types=1);

namespace WPDeveloper\BetterDocs\Dependencies\DI\Definition\Resolver;

use WPDeveloper\BetterDocs\Dependencies\DI\Definition\Definition;
use WPDeveloper\BetterDocs\Dependencies\DI\Definition\InstanceDefinition;
use WPDeveloper\BetterDocs\Dependencies\DI\DependencyException;
use WPDeveloper\BetterDocs\Dependencies\Psr\Container\NotFoundExceptionInterface;

/**
 * Injects dependencies on an existing instance.
 *
 * @since 5.0
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class InstanceInjector extends ObjectCreator
{
    /**
     * Injects dependencies on an existing instance.
     *
     * @param InstanceDefinition $definition
     *
     * {@inheritdoc}
     */
    public function resolve(Definition $definition, array $parameters = [])
    {
        try {
            $this->injectMethodsAndProperties($definition->getInstance(), $definition->getObjectDefinition());
        } catch (NotFoundExceptionInterface $e) {
            $message = sprintf(
                'Error while injecting dependencies into %s: %s',
                get_class($definition->getInstance()),
                $e->getMessage()
            );

            throw new DependencyException($message, 0, $e);
        }
    }

    public function isResolvable(Definition $definition, array $parameters = []) : bool
    {
        return true;
    }
}
