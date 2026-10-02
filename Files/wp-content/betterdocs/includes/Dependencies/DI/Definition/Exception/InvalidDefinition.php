<?php

declare(strict_types=1);

namespace WPDeveloper\BetterDocs\Dependencies\DI\Definition\Exception;

use WPDeveloper\BetterDocs\Dependencies\DI\Definition\Definition;

/**
 * Invalid WPDeveloper\BetterDocs\Dependencies\DI definitions.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class InvalidDefinition extends \Exception
{
    public static function create(Definition $definition, string $message, ?\Exception $previous = null) : self
    {
        return new self(sprintf(
            '%s' . PHP_EOL . 'Full definition:' . PHP_EOL . '%s',
            $message,
            (string) $definition
        ), 0, $previous);
    }
}
