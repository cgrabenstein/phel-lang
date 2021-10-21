<?php

declare(strict_types=1);

namespace Phel\Compiler;

use Phel\Compiler\Analyzer\Ast\AbstractNode;
use Phel\Compiler\Analyzer\Environment\NodeEnvironmentInterface;
use Phel\Compiler\Analyzer\Exceptions\AnalyzerException;
use Phel\Compiler\Compiler\CodeCompiler;
use Phel\Compiler\Emitter\EmitterResult;
use Phel\Compiler\Evaluator\Exceptions\CompiledCodeIsMalformedException;
use Phel\Compiler\Evaluator\Exceptions\FileException;
use Phel\Compiler\Exceptions\CompilerException;
use Phel\Compiler\Lexer\Exceptions\LexerValueException;
use Phel\Compiler\Lexer\Lexer;
use Phel\Compiler\Lexer\TokenStream;
use Phel\Compiler\Parser\Exceptions\UnexpectedParserException;
use Phel\Compiler\Parser\Exceptions\UnfinishedParserException;
use Phel\Compiler\Parser\ParserNode\FileNode;
use Phel\Compiler\Parser\ParserNode\NodeInterface;
use Phel\Compiler\Parser\ReadModel\ReaderResult;
use Phel\Compiler\Reader\Exceptions\ReaderException;
use Phel\Lang\TypeInterface;

interface CompilerFacadeInterface
{
    /**
     * @param TypeInterface|string|float|int|bool|null $x
     *
     * @throws AnalyzerException
     */
    public function analyze($x, NodeEnvironmentInterface $env): AbstractNode;

    /**
     * @throws CompilerException|UnfinishedParserException
     *
     * @return mixed The result of the executed code
     */
    public function eval(string $phelCode, int $startingLine = 1);

    /**
     * @throws CompilerException
     * @throws CompiledCodeIsMalformedException
     * @throws FileException
     */
    public function compile(string $phelCode, string $source = CodeCompiler::DEFAULT_SOURCE, bool $enableSourceMaps = false): EmitterResult;

    /**
     * @throws LexerValueException
     */
    public function lexString(string $code, string $source = Lexer::DEFAULT_SOURCE, bool $withLocation = true, int $startingLine = 1): TokenStream;

    /**
     * @throws UnexpectedParserException
     * @throws UnfinishedParserException
     */
    public function parseNext(TokenStream $tokenStream): ?NodeInterface;

    /**
     * @throws ReaderException
     */
    public function read(NodeInterface $parseTree): ReaderResult;

    /**
     * @throws UnexpectedParserException
     * @throws UnfinishedParserException
     */
    public function parseAll(TokenStream $tokenStream): FileNode;
}
