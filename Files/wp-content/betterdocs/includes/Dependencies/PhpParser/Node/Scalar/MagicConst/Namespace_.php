<?php

namespace WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Scalar\MagicConst;

use WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Scalar\MagicConst;

class Namespace_ extends MagicConst
{
    public function getName() {
        return '__NAMESPACE__';
    }
}