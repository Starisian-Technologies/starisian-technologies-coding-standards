<?php

declare(strict_types=1);

namespace Starisian\Standards\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * STD-TOOLCHAIN-001 §5 — Governed-action gate rule.
 *
 * A governed mutation entry point must call the platform consent/ability gate
 * before mutating state.
 *
 * Detection scope (what this rule actually checks):
 *   - Functions/methods annotated with @governed-mutation in their docblock.
 *   - Top-level functions whose names match WP AJAX/admin-post patterns
 *     (wp_ajax_*, wp_ajax_nopriv_*, admin_post_*).
 *
 * REST handlers, WP-CLI commands, and other entry points require explicit
 * @governed-mutation annotation for the rule to fire on them.
 *
 * STATUS: warn-only — not required until backing ADR is ratified (§10 directive 10).
 *
 * SCOPE: Entry points only — not every function that writes. The gate is asserted
 * at the entry point; internal helpers below it are out of scope.
 */
class GovernedActionGateRule implements Rule
{
    /** Docblock tags that mark a method as a governed mutation entry point. */
    private const GOVERNED_TAGS = ['@governed-mutation'];

    /** Function name patterns that identify WP entry points. */
    private const ENTRY_POINT_PATTERNS = [
        '/^(wp_ajax_|wp_ajax_nopriv_)/',  // AJAX handlers
        '/^admin_post_/',                   // admin-post handlers
    ];

    /** Gate function the platform requires to be called. */
    private const GATE_FUNCTION = 'assert_governed_action';

    public function getNodeTypes(): array
    {
        return [\PhpParser\Node\Stmt\ClassMethod::class, \PhpParser\Node\Stmt\Function_::class];
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!($node instanceof Function_) && !($node instanceof ClassMethod)) {
            return [];
        }

        if (!$this->isGovernedEntryPoint($node, $scope)) {
            return [];
        }

        if ($this->callsGate($node)) {
            return [];
        }

        $classPrefix = ($node instanceof ClassMethod)
            ? (($scope->getClassReflection()?->getName() ?? '<unknown>') . '::')
            : '';
        $name = $classPrefix . $node->name->name;

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'STD-TOOLCHAIN-001 §5: Governed mutation entry point %s() must call %s() before mutating state.',
                    $name,
                    self::GATE_FUNCTION
                )
            )
            ->tip('Add ' . self::GATE_FUNCTION . '() as the first call in this entry point.')
            ->build(),
        ];
    }

    private function isGovernedEntryPoint(Function_|ClassMethod $node, Scope $scope): bool
    {
        // Check for explicit annotation
        $docComment = $node->getDocComment()?->getText() ?? '';
        foreach (self::GOVERNED_TAGS as $tag) {
            if (str_contains($docComment, $tag)) {
                return true;
            }
        }

        // Check WP entry point naming patterns (top-level functions only)
        if ($node instanceof Function_) {
            $name = $node->name->name;
            foreach (self::ENTRY_POINT_PATTERNS as $pattern) {
                if (preg_match($pattern, $name)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function callsGate(Function_|ClassMethod $node): bool
    {
        $stmts = $node->stmts ?? [];
        return $this->nodesContainGateCall($stmts);
    }

    /**
     * Recursively walk any Node array looking for a FuncCall to the gate function.
     * Handles standalone calls, fully-qualified calls, and calls nested inside
     * other expressions (assignments, ternaries, argument lists, etc.).
     *
     * @param array<mixed> $nodes
     */
    private function nodesContainGateCall(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if (!($node instanceof Node)) {
                continue;
            }
            if ($node instanceof Node\Expr\FuncCall) {
                $name = $node->name;
                if ($name instanceof Node\Name &&
                    $name->getLast() === self::GATE_FUNCTION
                ) {
                    return true;
                }
            }
            foreach ($node->getSubNodeNames() as $subName) {
                $sub = $node->$subName;
                $items = is_array($sub) ? $sub : [$sub];
                if ($this->nodesContainGateCall($items)) {
                    return true;
                }
            }
        }
        return false;
    }
}
