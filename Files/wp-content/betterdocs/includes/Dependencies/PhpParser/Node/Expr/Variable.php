<?php

namespace WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Expr;

use WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Expr;

class Variable extends Expr
{
    /** @var string|Expr Name */
    public $name;

    /**
     * Constructs a variable node.
     *
     * @param string|Expr $name       Name
     * @param array                      $attributes Additional attributes
     */
    public function __construct($name, array $attributes = array()) {
        parent::__construct($attributes);
        $this->name = $name;
    }

    public function getSubNodeNames() {
        return array('name');
    }
}
