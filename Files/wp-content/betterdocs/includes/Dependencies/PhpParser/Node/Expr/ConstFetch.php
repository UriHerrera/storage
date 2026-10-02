<?php

namespace WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Expr;

use WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Expr;
use WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Name;

class ConstFetch extends Expr
{
    /** @var Name Constant name */
    public $name;

    /**
     * Constructs a const fetch node.
     *
     * @param Name  $name       Constant name
     * @param array $attributes Additional attributes
     */
    public function __construct(Name $name, array $attributes = array()) {
        parent::__construct($attributes);
        $this->name = $name;
    }

    public function getSubNodeNames() {
        return array('name');
    }
}
