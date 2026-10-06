<?php

namespace WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Stmt;

use WPDeveloper\BetterDocs\Dependencies\PhpParser\Node;

abstract class TraitUseAdaptation extends Node\Stmt
{
    /** @var Node\Name Trait name */
    public $trait;
    /** @var string Method name */
    public $method;
}
