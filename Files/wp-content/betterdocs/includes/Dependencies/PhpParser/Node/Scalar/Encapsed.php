<?php

namespace WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Scalar;

use WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Expr;
use WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Scalar;

class Encapsed extends Scalar
{
    /** @var Expr[] list of string parts */
    public $parts;

    /**
     * Constructs an encapsed string node.
     *
     * @param array $parts      Encaps list
     * @param array $attributes Additional attributes
     */
    public function __construct(array $parts, array $attributes = array()) {
        parent::__construct($attributes);
        $this->parts = $parts;
    }

    public function getSubNodeNames() {
        return array('parts');
    }
}
