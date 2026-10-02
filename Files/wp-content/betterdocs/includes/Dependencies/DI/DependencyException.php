<?php

declare(strict_types=1);

namespace WPDeveloper\BetterDocs\Dependencies\DI;

use WPDeveloper\BetterDocs\Dependencies\Psr\Container\ContainerExceptionInterface;

/**
 * Exception for the Container.
 */
class DependencyException extends \Exception implements ContainerExceptionInterface
{
}
