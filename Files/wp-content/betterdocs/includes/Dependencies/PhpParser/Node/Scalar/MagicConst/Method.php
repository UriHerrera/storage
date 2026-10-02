<?php

namespace WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Scalar\MagicConst;

use WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Scalar\MagicConst;

class Method extends MagicConst
{
    public function getName() {
        return '__METHOD__';
    }
}