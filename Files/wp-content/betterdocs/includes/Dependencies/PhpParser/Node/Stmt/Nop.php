<?php

namespace WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Stmt;

use WPDeveloper\BetterDocs\Dependencies\PhpParser\Node;

/** Nop/empty statement (;). */
class Nop extends Node\Stmt
{
    public function getSubNodeNames() {
        return array();
    }
}
