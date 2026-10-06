<?php

namespace WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Scalar\MagicConst;

use WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Scalar\MagicConst;

class Function_ extends MagicConst
{
    public function getName() {
        return '__FUNCTION__';
    }
}