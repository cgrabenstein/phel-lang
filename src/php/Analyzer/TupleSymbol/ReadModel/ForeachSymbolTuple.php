<?php

declare(strict_types=1);

namespace Phel\Analyzer\TupleSymbol\ReadModel;

use Phel\Ast\Node;
use Phel\Lang\Symbol;
use Phel\NodeEnvironment;

final class ForeachSymbolTuple
{
    private array $lets;
    private NodeEnvironment $bodyEnv;
    private Node $listExpr;
    private Symbol $valueSymbol;
    private ?Symbol $keySymbol;

    public function __construct(
        array $lets,
        NodeEnvironment $bodyEnv,
        Node $listExpr,
        Symbol $valueSymbol,
        ?Symbol $keySymbol = null
    ) {
        $this->lets = $lets;
        $this->bodyEnv = $bodyEnv;
        $this->listExpr = $listExpr;
        $this->valueSymbol = $valueSymbol;
        $this->keySymbol = $keySymbol;
    }

    public function lets(): array
    {
        return $this->lets;
    }

    public function bodyEnv(): NodeEnvironment
    {
        return $this->bodyEnv;
    }

    public function listExpr(): Node
    {
        return $this->listExpr;
    }

    public function valueSymbol(): Symbol
    {
        return $this->valueSymbol;
    }

    public function keySymbol(): ?Symbol
    {
        return $this->keySymbol;
    }
}
