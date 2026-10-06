<?php
// phpcs:ignoreFile -- Bundled third-party (Mozart) dependency; exempt from plugin coding standards.

declare(strict_types=1);

namespace WPDeveloper\BetterDocs\Dependencies\DI\Definition\Source;

use WPDeveloper\BetterDocs\Dependencies\DI\Definition\ArrayDefinition;
use WPDeveloper\BetterDocs\Dependencies\DI\Definition\AutowireDefinition;
use WPDeveloper\BetterDocs\Dependencies\DI\Definition\DecoratorDefinition;
use WPDeveloper\BetterDocs\Dependencies\DI\Definition\Definition;
use WPDeveloper\BetterDocs\Dependencies\DI\Definition\Exception\InvalidDefinition;
use WPDeveloper\BetterDocs\Dependencies\DI\Definition\FactoryDefinition;
use WPDeveloper\BetterDocs\Dependencies\DI\Definition\Helper\DefinitionHelper;
use WPDeveloper\BetterDocs\Dependencies\DI\Definition\ValueDefinition;

/**
 * Turns raw definitions/definition helpers into definitions ready
 * to be resolved or compiled.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class DefinitionNormalizer
{
    /**
     * @var Autowiring
     */
    private $autowiring;

    public function __construct(Autowiring $autowiring)
    {
        $this->autowiring = $autowiring;
    }

    /**
     * Normalize a definition that is *not* nested in another one.
     *
     * This is usually a definition declared at the root of a definition array.
     *
     * @param mixed $definition
     * @param string $name The definition name.
     *
     * @throws InvalidDefinition
     */
    public function normalizeRootDefinition($definition, string $name) : Definition
    {
        if ($definition instanceof DefinitionHelper) {
            $definition = $definition->getDefinition($name);
        } elseif (is_array($definition)) {
            $definition = new ArrayDefinition($definition);
        } elseif ($definition instanceof \Closure) {
            $definition = new FactoryDefinition($name, $definition);
        } elseif (! $definition instanceof Definition) {
            $definition = new ValueDefinition($definition);
        }

        if ($definition instanceof AutowireDefinition) {
            $definition = $this->autowiring->autowire($name, $definition);
        }

        $definition->setName($name);

        try {
            $definition->replaceNestedDefinitions([$this, 'normalizeNestedDefinition']);
        } catch (InvalidDefinition $e) {
            throw InvalidDefinition::create($definition, sprintf(
                'Definition "%s" contains an error: %s',
                $definition->getName(),
                $e->getMessage()
            ), $e);
        }

        return $definition;
    }

    /**
     * Normalize a definition that is nested in another one.
     *
     * @param mixed $definition
     * @return mixed
     *
     * @throws InvalidDefinition
     */
    public function normalizeNestedDefinition($definition)
    {
        $name = '<nested definition>';

        if ($definition instanceof DefinitionHelper) {
            $definition = $definition->getDefinition($name);
        } elseif (is_array($definition)) {
            $definition = new ArrayDefinition($definition);
        } elseif ($definition instanceof \Closure) {
            $definition = new FactoryDefinition($name, $definition);
        }

        if ($definition instanceof DecoratorDefinition) {
            throw new InvalidDefinition('Decorators cannot be nested in another definition');
        }

        if ($definition instanceof AutowireDefinition) {
            $definition = $this->autowiring->autowire($name, $definition);
        }

        if ($definition instanceof Definition) {
            $definition->setName($name);

            // Recursively traverse nested definitions
            $definition->replaceNestedDefinitions([$this, 'normalizeNestedDefinition']);
        }

        return $definition;
    }
}
