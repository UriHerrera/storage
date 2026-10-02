<?php

declare(strict_types=1);

namespace WPDeveloper\BetterDocs\Dependencies\DI\Definition\Source;

use WPDeveloper\BetterDocs\Dependencies\DI\Definition\Definition;
use WPDeveloper\BetterDocs\Dependencies\DI\Definition\Exception\InvalidDefinition;

/**
 * Source of definitions for entries of the container.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface DefinitionSource
{
    /**
     * Returns the WPDeveloper\BetterDocs\Dependencies\DI definition for the entry name.
     *
     * @throws InvalidDefinition An invalid definition was found.
     * @return Definition|null
     */
    public function getDefinition(string $name);

    /**
     * @return Definition[] Definitions indexed by their name.
     */
    public function getDefinitions() : array;
}
