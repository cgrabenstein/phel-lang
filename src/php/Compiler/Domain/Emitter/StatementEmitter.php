<?php

declare(strict_types=1);

namespace Phel\Compiler\Domain\Emitter;

use Phel\Compiler\Domain\Analyzer\Ast\AbstractNode;
use Phel\Compiler\Domain\Emitter\OutputEmitter\SourceMap\SourceMapGenerator;

final class StatementEmitter implements StatementEmitterInterface
{
    private SourceMapGenerator $sourceMapGenerator;
    private OutputEmitterInterface $outputEmitter;

    public function __construct(
        SourceMapGenerator $sourceMapGenerator,
        OutputEmitterInterface $outputEmitter,
    ) {
        $this->sourceMapGenerator = $sourceMapGenerator;
        $this->outputEmitter = $outputEmitter;
    }

    public function emitNode(AbstractNode $node, bool $enableSourceMaps): EmitterResult
    {
        $this->outputEmitter->resetIndentLevel();
        $this->outputEmitter->resetSourceMapState();

        return new EmitterResult(
            $enableSourceMaps,
            $this->phpCode($node),
            $this->sourceMap($enableSourceMaps),
            $this->source($node),
        );
    }

    private function phpCode(AbstractNode $node): string
    {
        ob_start();
        $this->outputEmitter->emitNode($node);

        return ob_get_clean();
    }

    private function sourceMap(bool $enableSourceMaps): string
    {
        if (!$enableSourceMaps) {
            return '';
        }

        return $this->sourceMapGenerator->encode(
            $this->outputEmitter->getSourceMapState()->getMappings(),
        );
    }

    private function source(AbstractNode $node): string
    {
        $sourceLocation = $node->getStartSourceLocation();

        return $sourceLocation ? $sourceLocation->getFile() : 'string';
    }
}
