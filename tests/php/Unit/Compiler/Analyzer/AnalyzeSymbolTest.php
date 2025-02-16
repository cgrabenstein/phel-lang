<?php

declare(strict_types=1);

namespace PhelTest\Unit\Compiler\Analyzer;

use Phel\Compiler\Domain\Analyzer\Analyzer;
use Phel\Compiler\Domain\Analyzer\Ast\GlobalVarNode;
use Phel\Compiler\Domain\Analyzer\Ast\LocalVarNode;
use Phel\Compiler\Domain\Analyzer\Ast\PhpVarNode;
use Phel\Compiler\Domain\Analyzer\Environment\GlobalEnvironment;
use Phel\Compiler\Domain\Analyzer\Environment\NodeEnvironment;
use Phel\Compiler\Domain\Analyzer\Exceptions\AnalyzerException;
use Phel\Compiler\Domain\Analyzer\TypeAnalyzer\AnalyzeSymbol;
use Phel\Lang\Registry;
use Phel\Lang\Symbol;
use Phel\Lang\TypeFactory;
use PHPUnit\Framework\TestCase;

final class AnalyzeSymbolTest extends TestCase
{
    private AnalyzeSymbol $symbolAnalyzer;

    public function setUp(): void
    {
        $this->symbolAnalyzer = new AnalyzeSymbol(new Analyzer(new GlobalEnvironment()));
        Registry::getInstance()->clear();
    }

    public function test_php_symbol(): void
    {
        $env = NodeEnvironment::empty();
        self::assertEquals(
            new PhpVarNode($env, 'is_array', null),
            $this->symbolAnalyzer->analyze(Symbol::createForNamespace('php', 'is_array'), $env),
        );
    }

    public function test_local_var(): void
    {
        $env = NodeEnvironment::empty()->withLocals([Symbol::create('a')]);
        self::assertEquals(
            new LocalVarNode($env, Symbol::create('a'), null),
            $this->symbolAnalyzer->analyze(Symbol::create('a'), $env),
        );
    }

    public function test_local_shadowed_var(): void
    {
        $env = NodeEnvironment::empty()
            ->withLocals([Symbol::create('a')])
            ->withShadowedLocal(Symbol::create('a'), Symbol::create('b'));

        self::assertEquals(
            new LocalVarNode($env, Symbol::create('b'), null),
            $this->symbolAnalyzer->analyze(Symbol::create('a'), $env),
        );
    }

    public function test_global_var(): void
    {
        $globalEnv = new GlobalEnvironment();
        $globalEnv->setNs('test');
        $globalEnv->addDefinition('test', Symbol::create('a'));
        $symbolAnalyzer = new AnalyzeSymbol(new Analyzer($globalEnv));

        $env = NodeEnvironment::empty();
        self::assertEquals(
            new GlobalVarNode($env, 'test', Symbol::create('a'), TypeFactory::getInstance()->emptyPersistentMap(), null),
            $symbolAnalyzer->analyze(Symbol::create('a'), $env),
        );
    }

    public function test_undefined_global_var(): void
    {
        $this->expectException(AnalyzerException::class);
        $this->expectExceptionMessage("Cannot resolve symbol 'a'");

        $env = NodeEnvironment::empty();
        $this->symbolAnalyzer->analyze(Symbol::create('a'), $env);
    }

    public function test_local_var_wins_over_global_var(): void
    {
        $globalEnv = new GlobalEnvironment();
        $globalEnv->setNs('test');
        $globalEnv->addDefinition('test', Symbol::create('a'));
        $symbolAnalyzer = new AnalyzeSymbol(new Analyzer($globalEnv));

        $env = NodeEnvironment::empty()->withLocals([Symbol::create('a')]);
        self::assertEquals(
            new LocalVarNode($env, Symbol::create('a'), null),
            $symbolAnalyzer->analyze(Symbol::create('a'), $env),
        );
    }
}
