<?php namespace WPDeveloper\BetterDocs\Dependencies\SuperClosure\Analyzer\Visitor;

use WPDeveloper\BetterDocs\Dependencies\PhpParser\Node as AstNode;
use WPDeveloper\BetterDocs\Dependencies\PhpParser\Node\Expr\Variable as VariableNode;
use WPDeveloper\BetterDocs\Dependencies\PhpParser\NodeVisitorAbstract as NodeVisitor;

/**
 * Detects if the closure's AST contains a $this variable.
 *
 * @internal
 */
final class ThisDetectorVisitor extends NodeVisitor
{
    /**
     * @var bool
     */
    public $detected = false;

    public function leaveNode(AstNode $node)
    {
        if ($node instanceof VariableNode) {
            if ($node->name === 'this') {
                $this->detected = true;
            }
        }
    }
}
