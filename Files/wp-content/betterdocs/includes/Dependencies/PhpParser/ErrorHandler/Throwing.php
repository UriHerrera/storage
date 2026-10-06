<?php

namespace WPDeveloper\BetterDocs\Dependencies\PhpParser\ErrorHandler;

use WPDeveloper\BetterDocs\Dependencies\PhpParser\Error;
use WPDeveloper\BetterDocs\Dependencies\PhpParser\ErrorHandler;

/**
 * Error handler that handles all errors by throwing them.
 *
 * This is the default strategy used by all components.
 */
class Throwing implements ErrorHandler
{
    public function handleError(Error $error) {
        throw $error;
    }
}