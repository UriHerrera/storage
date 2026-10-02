<?php

namespace WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Scalar\MagicConst;

use WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Scalar\MagicConst;

class Line extends MagicConst
{
    public function getName() {
        return '__LINE__';
    }
}