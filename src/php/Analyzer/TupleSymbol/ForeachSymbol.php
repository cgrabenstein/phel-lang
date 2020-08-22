<?php

declare(strict_types=1);

namespace Phel\Analyzer\TupleSymbol;

use Phel\Analyzer\TupleSymbol\ReadModel\ForeachSymbolTuple;
use Phel\Analyzer\WithAnalyzer;
use Phel\Ast\ForeachNode;
use Phel\Exceptions\AnalyzerException;
use Phel\Lang\Symbol;
use Phel\Lang\Tuple;
use Phel\NodeEnvironment;

final class ForeachSymbol implements TupleSymbolAnalyzer
{
    use WithAnalyzer;

    public function analyze(Tuple $tuple, NodeEnvironment $env): ForeachNode
    {
        $tupleCount = count($tuple);
        if ($tupleCount < 2) {
            throw AnalyzerException::withLocation("At least two arguments are required for 'foreach", $tuple);
        }

        $foreachTuple = $tuple[1];
        if (!($foreachTuple instanceof Tuple)) {
            throw AnalyzerException::withLocation("First argument of 'foreach must be a tuple.", $tuple);
        }

        $firstArgCount = count($foreachTuple);
        if ($firstArgCount !== 2 && $firstArgCount !== 3) {
            throw AnalyzerException::withLocation("Tuple of 'foreach must have exactly two or three elements.", $tuple);
        }

        $foreachSymbolTuple = $this->buildForeachSymbolTuple($foreachTuple, $env);

        $bodyExpr = $this->analyzer->analyze(
            $this->buildTupleBody($foreachSymbolTuple->lets(), $tuple),
            $foreachSymbolTuple->bodyEnv()->withContext(NodeEnvironment::CTX_STMT)
        );

        return new ForeachNode(
            $env,
            $bodyExpr,
            $foreachSymbolTuple->listExpr(),
            $foreachSymbolTuple->valueSymbol(),
            $foreachSymbolTuple->keySymbol(),
            $tuple->getStartLocation()
        );
    }

    private function buildForeachSymbolTuple(Tuple $foreachTuple, NodeEnvironment $env): ForeachSymbolTuple
    {
        if (count($foreachTuple) === 2) {
            return $this->buildForeachTupleWhen2Args($foreachTuple, $env);
        }

        return $this->buildForeachTupleWhen3Args($foreachTuple, $env);
    }

    private function buildForeachTupleWhen2Args(Tuple $foreachTuple, NodeEnvironment $env): ForeachSymbolTuple
    {
        $lets = [];
        $valueSymbol = $foreachTuple[0];

        if (!($valueSymbol instanceof Symbol)) {
            $tmpSym = Symbol::gen();
            $lets[] = $valueSymbol;
            $lets[] = $tmpSym;
            $valueSymbol = $tmpSym;
        }
        $bodyEnv = $env->withMergedLocals([$valueSymbol]);
        $listExpr = $this->analyzer->analyze(
            $foreachTuple[1],
            $env->withContext(NodeEnvironment::CTX_EXPR)
        );

        return new ForeachSymbolTuple($lets, $bodyEnv, $listExpr, $valueSymbol);
    }

    private function buildForeachTupleWhen3Args(Tuple $foreachTuple, NodeEnvironment $env): ForeachSymbolTuple
    {
        $lets = [];
        [$keySymbol, $valueSymbol] = $foreachTuple;

        if (!($keySymbol instanceof Symbol)) {
            $tmpSym = Symbol::gen();
            $lets[] = $keySymbol;
            $lets[] = $tmpSym;
            $keySymbol = $tmpSym;
        }

        if (!($valueSymbol instanceof Symbol)) {
            $tmpSym = Symbol::gen();
            $lets[] = $valueSymbol;
            $lets[] = $tmpSym;
            $valueSymbol = $tmpSym;
        }

        $bodyEnv = $env->withMergedLocals([$valueSymbol, $keySymbol]);
        $listExpr = $this->analyzer->analyze(
            $foreachTuple[2],
            $env->withContext(NodeEnvironment::CTX_EXPR)
        );

        return new ForeachSymbolTuple($lets, $bodyEnv, $listExpr, $valueSymbol, $keySymbol);
    }

    private function buildTupleBody(array $lets, Tuple $tuple): Tuple
    {
        $bodys = [];
        for ($i = 2, $iMax = count($tuple); $i < $iMax; $i++) {
            $bodys[] = $tuple[$i];
        }

        if (!empty($lets)) {
            return Tuple::create(
                Symbol::create(Symbol::NAME_LET),
                new Tuple($lets, true),
                ...$bodys
            );
        }

        return Tuple::create(
            Symbol::create(Symbol::NAME_DO),
            ...$bodys
        );
    }
}
