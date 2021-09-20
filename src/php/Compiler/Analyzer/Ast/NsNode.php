<?php

declare(strict_types=1);

namespace Phel\Compiler\Analyzer\Ast;

use Phel\Compiler\Analyzer\Environment\NodeEnvironment;
use Phel\Lang\SourceLocation;
use Phel\Lang\Symbol;

final class NsNode extends AbstractNode
{
    /** @var Symbol[] */
    private array $requireNs;

    private string $namespace;

    /**
     * @param Symbol[] $requireNs
     */
    public function __construct(string $namespace, array $requireNs, ?SourceLocation $sourceLocation = null)
    {
        parent::__construct(NodeEnvironment::empty(), $sourceLocation);
        $this->requireNs = $requireNs;
        if ($namespace !== 'phel\\core') {
            // All other files implicitly depend on phel\core
            $this->requireNs[] = Symbol::create('phel\\core');
        }
        $this->namespace = $namespace;
    }

    /**
     * @return Symbol[]
     */
    public function getRequireNs(): array
    {
        return $this->requireNs;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }
}
