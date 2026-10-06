<?php

namespace WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Scalar\MagicConst;

use WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Scalar\MagicConst;

class Trait_ extends MagicConst
{
    public function getName() {
        return '__TRAIT__';
    }
}