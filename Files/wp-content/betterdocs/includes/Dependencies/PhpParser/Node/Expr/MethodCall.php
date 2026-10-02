<?php

namespace WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Expr;

use WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Arg;
use WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Expr;

class MethodCall extends Expr
{
    /** @var Expr Variable holding object */
    public $var;
    /** @var string|Expr Method name */
    public $name;
    /** @var Arg[] Arguments */
    public $args;

    /**
     * Constructs a function call node.
     *
     * @param Expr        $var        Variable holding object
     * @param string|Expr $name       Method name
     * @param Arg[]       $args       Arguments
     * @param array       $attributes Additional attributes
     */
    public function __construct(Expr $var, $name, array $args = array(), array $attributes = array()) {
        parent::__construct($attributes);
        $this->var = $var;
        $this->name = $name;
        $this->args = $args;
    }

    public function getSubNodeNames() {
        return array('var', 'name', 'args');
    }
}
