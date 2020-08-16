<?php

declare(strict_types=1);

namespace PhelTest\Analyzer\TupleSymbol;

use Generator;
use Phel\Analyzer;
use Phel\Analyzer\TupleSymbol\FnSymbol;
use Phel\Ast\DoNode;
use Phel\Ast\FnNode;
use Phel\Exceptions\PhelCodeException;
use Phel\GlobalEnvironment;
use Phel\Lang\Symbol;
use Phel\Lang\Tuple;
use Phel\NodeEnvironment;
use PHPUnit\Framework\TestCase;

final class FnSymbolTest extends TestCase
{
    private Analyzer $analyzer;

    public function setUp(): void
    {
        $this->analyzer = new Analyzer(new GlobalEnvironment());
    }

    public function testRequiresAtLeastOneArg(): void
    {
        $this->expectException(PhelCodeException::class);
        $this->expectExceptionMessage("'fn requires at least one argument");

        $tuple = Tuple::create(
            Symbol::create(Symbol::NAME_FN)
        );

        (new FnSymbol($this->analyzer))->analyze($tuple, NodeEnvironment::empty());
    }

    public function testSecondArgMustBeATuple(): void
    {
        $this->expectException(PhelCodeException::class);
        $this->expectExceptionMessage("Second argument of 'fn must be a Tuple");

        // This is the same as: (fn anything)
        $tuple = Tuple::create(
            Symbol::create(Symbol::NAME_FN),
            Symbol::create('anything')
        );

        $this->analyze($tuple);
    }

    public function testIsNotVariadic(): void
    {
        // This is the same as: (fn [anything])
        $tuple = Tuple::create(
            Symbol::create(Symbol::NAME_FN),
            Tuple::createBracket(
                Symbol::create('anything')
            )
        );

        $fnNode = $this->analyze($tuple);

        self::assertFalse($fnNode->isVariadic());
    }

    /**
     * @dataProvider providerVarNamesMustStartWithLetterOrUnderscore
     */
    public function testVarNamesMustStartWithLetterOrUnderscore(string $paramName, bool $error): void
    {
        if ($error) {
            $this->expectException(PhelCodeException::class);
            $this->expectExceptionMessageMatches('/(Variable names must start with a letter or underscore)*/i');
        } else {
            self::assertTrue(true); // In order to have an assertion without an error
        }

        // This is the same as: (fn [paramName])
        $tuple = Tuple::create(
            Symbol::create(Symbol::NAME_FN),
            Tuple::createBracket(
                Symbol::create($paramName)
            )
        );

        $this->analyze($tuple);
    }

    public function providerVarNamesMustStartWithLetterOrUnderscore(): Generator
    {
        yield 'Start with a letter' => [
            'paramName' => 'param-1',
            'error' => false,
        ];

        yield 'Start with an underscore' => [
            'paramName' => '_param-2',
            'error' => false,
        ];

        yield 'Start with a number' => [
            'paramName' => '1-param-3',
            'error' => true,
        ];

        yield 'Start with an ampersand' => [
            'paramName' => '&-param-4',
            'error' => true,
        ];

        yield 'Start with a space' => [
            'paramName' => ' param-5',
            'error' => true,
        ];
    }

    public function testOnlyOneSymbolCanFollowTheAmpersandParameter(): void
    {
        $this->expectException(PhelCodeException::class);
        $this->expectExceptionMessage('Unsupported parameter form, only one symbol can follow the & parameter');

        // This is the same as: (fn [& param-1 param-2])
        $tuple = Tuple::create(
            Symbol::create(Symbol::NAME_FN),
            Tuple::createBracket(
                Symbol::create('&'),
                Symbol::create('param-1'),
                Symbol::create('param-2'),
            )
        );

        $this->analyze($tuple);
    }

    /** @dataProvider providerGetParams */
    public function testGetParams(Tuple $tuple, array $expectedParams): void
    {
        $node = $this->analyze($tuple);

        self::assertEquals($expectedParams, $node->getParams());
    }

    public function providerGetParams(): Generator
    {
        yield '(fn [& param-1])' => [
            'tuple' => Tuple::create(
                Symbol::create(Symbol::NAME_FN),
                Tuple::createBracket(
                    Symbol::create('&'),
                    Symbol::create('param-1'),
                )
            ),
            'expectedParams' => [
                Symbol::create('param-1'),
            ],
        ];

        yield '(fn [param-1 param-2 param-3])' => [
            'tuple' => Tuple::create(
                Symbol::create(Symbol::NAME_FN),
                Tuple::createBracket(
                    Symbol::create('param-1'),
                    Symbol::create('param-2'),
                    Symbol::create('param-3'),
                )
            ),
            'expectedParams' => [
                Symbol::create('param-1'),
                Symbol::create('param-2'),
                Symbol::create('param-3'),
            ],
        ];
    }

    /** @dataProvider providerGetBody */
    public function testGetBody(Tuple $tuple, string $expectedBodyInstanceOf): void
    {
        $node = $this->analyze($tuple);

        self::assertInstanceOf($expectedBodyInstanceOf, $node->getBody());
    }

    public function providerGetBody(): Generator
    {
        yield 'DoNode body => (fn [x] x)' => [
            'tuple' => Tuple::create(
                Symbol::create(Symbol::NAME_FN),
                Tuple::createBracket(
                    Symbol::create('x'),
                ),
                Symbol::create('x'),
            ),
            'expectedBodyInstanceOf' => DoNode::class,
        ];
    }

    private function analyze(Tuple $tuple): FnNode
    {
        return (new FnSymbol($this->analyzer))->analyze($tuple, NodeEnvironment::empty());
    }
}
