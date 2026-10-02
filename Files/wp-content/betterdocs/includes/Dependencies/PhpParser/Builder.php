<?php

namespace WPDeveloper\BetterDocs\Dependencies\PhpParser;

interface Builder
{
    /**
     * Returns the built node.
     *
     * @return Node The built node
     */
    public function getNode();
}