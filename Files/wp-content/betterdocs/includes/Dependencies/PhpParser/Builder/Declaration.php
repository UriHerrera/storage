<?php

namespace WPDeveloper\BetterDocs\Dependencies\PhpParser\Builder;

use WPDeveloper\BetterDocs\Dependencies\PhpParser;

abstract class Declaration extends WPDeveloper\BetterDocs\Dependencies\PhpParser\BuilderAbstract
{
    protected $attributes = array();

    abstract public function addStmt($stmt);

    /**
     * Adds multiple statements.
     *
     * @param array $stmts The statements to add
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function addStmts(array $stmts) {
        foreach ($stmts as $stmt) {
            $this->addStmt($stmt);
        }

        return $this;
    }

    /**
     * Sets doc comment for the declaration.
     *
     * @param WPDeveloper\BetterDocs\Dependencies\PhpParser\Comment\Doc|string $docComment Doc comment to set
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function setDocComment($docComment) {
        $this->attributes['comments'] = array(
            $this->normalizeDocComment($docComment)
        );

        return $this;
    }
}