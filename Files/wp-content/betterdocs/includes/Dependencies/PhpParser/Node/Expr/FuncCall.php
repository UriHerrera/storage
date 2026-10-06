<?php

namespace WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Expr;

use WPDeveloper\BetterDocs\Dependencies\PhpParser\Node;
use WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Expr;

class FuncCall extends Expr
{
    /** @var Node\Name|Expr Function name */
    public $name;
    /** @var Node\Arg[] Arguments */
    public $args;

    /**
     * Constructs a function call node.
     *
     * @param Node\Name|Expr $name       Function name
     * @param Node\Arg[]                    $args       Arguments
     * @param array                                   $attributes Additional attributes
     */
    public function __construct($name, array $args = array(), array $attributes = array()) {
        parent::__construct($attributes);
        $this->name = $name;
        $this->args = $args;
    }

    public function getSubNodeNames() {
        return array('name', 'args');
    }
}
