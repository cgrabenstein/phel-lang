<?php

declare(strict_types=1);

namespace Phel;

use Phel\Analyzer\AnalyzeArray;
use Phel\Analyzer\AnalyzeBracketTuple;
use Phel\Analyzer\AnalyzeLiteral;
use Phel\Analyzer\AnalyzeSymbol;
use Phel\Analyzer\AnalyzeTable;
use Phel\Analyzer\AnalyzeTuple;
use Phel\Ast\Node;
use Phel\Exceptions\AnalyzerException;
use Phel\Lang\Keyword;
use Phel\Lang\AbstractType;
use Phel\Lang\PhelArray;
use Phel\Lang\Symbol;
use Phel\Lang\Table;
use Phel\Lang\Tuple;

final class Analyzer
{
    private GlobalEnvironment $globalEnvironment;

    public function __construct(GlobalEnvironment $globalEnvironment)
    {
        $this->globalEnvironment = $globalEnvironment;
    }

    public function getGlobalEnvironment(): GlobalEnvironment
    {
        return $this->globalEnvironment;
    }

    /** @param AbstractType|scalar|null $x */
    public function analyzeInEmptyEnv($x): Node
    {
        return $this->analyze($x, NodeEnvironment::empty());
    }

    /** @param AbstractType|scalar|null $x */
    public function analyze($x, NodeEnvironment $env): Node
    {
        if ($this->isLiteral($x)) {
            return (new AnalyzeLiteral())->toNode($x, $env);
        }

        if ($x instanceof Symbol) {
            return (new AnalyzeSymbol($this))->toNode($x, $env);
        }

        if ($x instanceof Tuple && $x->isUsingBracket()) {
            return (new AnalyzeBracketTuple($this))->toNode($x, $env);
        }

        if ($x instanceof PhelArray) {
            return (new AnalyzeArray($this))->toNode($x, $env);
        }

        if ($x instanceof Table) {
            return (new AnalyzeTable($this))->toNode($x, $env);
        }

        if ($x instanceof Tuple) {
            return (new AnalyzeTuple($this))->toNode($x, $env);
        }

        throw new AnalyzerException('Unhandled type: ' . var_export($x, true));
    }

    /** @param AbstractType|scalar|null $x */
    private function isLiteral($x): bool
    {
        return is_string($x)
            || is_float($x)
            || is_int($x)
            || is_bool($x)
            || $x === null
            || $x instanceof Keyword;
    }
}
