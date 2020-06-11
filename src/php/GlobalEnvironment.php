<?php

declare(strict_types=1);

namespace Phel;

use Phel\Ast\GlobalVarNode;
use Phel\Ast\Node;
use Phel\Ast\PhpClassNameNode;
use Phel\Lang\IMeta;
use Phel\Lang\Symbol;
use Phel\Lang\Table;

final class GlobalEnvironment
{
    private string $ns = 'user';

    private array $definitions = [];

    private array $requireAliases = [];

    private array $useAliases = [];

    public function getNs(): string
    {
        return $this->ns;
    }

    public function setNs(string $ns): void
    {
        $this->ns = $ns;
    }

    public function addDefinition(string $namespace, Symbol $name, Table $meta): void
    {
        if (!array_key_exists($namespace, $this->definitions)) {
            $this->definitions[$namespace] = [];
        }

        $this->definitions[$namespace][$name->getName()] = $meta;
    }

    public function hasDefinition(string $namespace, Symbol $name): bool
    {
        return (isset($this->definitions[$namespace][$name->getName()]));
    }

    public function getDefinition(string $namespace, Symbol $name): ?Table
    {
        if ($this->hasDefinition($namespace, $name)) {
            return $this->definitions[$namespace][$name->getName()];
        }

        return null;
    }

    public function addRequireAlias(Symbol $name, Symbol $fullName): void
    {
        $this->requireAliases[$name->getName()] = $fullName;
    }

    public function hasRequireAlias(Symbol $name): bool
    {
        return isset($this->requireAliases[$name->getName()]);
    }

    public function addUseAlias(Symbol $alias, Symbol $fullName): void
    {
        $this->useAliases[$alias->getName()] = $fullName;
    }

    public function hasUseAlias(Symbol $alias): bool
    {
        return isset($this->useAliases[$alias->getName()]);
    }

    public function resolve(Symbol $name, NodeEnvironment $env): ?Node
    {
        $strName = $name->getName();

        if ($strName[0] === '\\') {
            return new PhpClassNameNode($env, $name, $name->getStartLocation());
        }

        if ($this->hasUseAlias($name)) {
            /** @var Symbol $alias */
            $alias = $this->useAliases[$strName];
            $alias->copyLocationFrom($name);
            return new PhpClassNameNode($env, $alias, $name->getStartLocation());
        }

        $pos = strpos($strName, '/');

        if ($pos !== false && $pos > 0) {
            return $this->resolveWithAlias($strName, $pos, $env, $name);
        }

        return $this->resolveWithoutAlias($name, $env);
    }

    private function resolveWithAlias(string $strName, int $pos, NodeEnvironment $env, Symbol $name): ?GlobalVarNode
    {
        $alias = substr($strName, 0, $pos);

        if (!isset($this->requireAliases[$alias])) {
            return null;
        }

        $namespace = $this->requireAliases[$alias];
        $finalName = new Symbol(substr($strName, $pos + 1));

        $def = $this->getDefinition($namespace, $finalName);
        if ($def) {
            return new GlobalVarNode($env, $namespace, $finalName, $def, $name->getStartLocation());
        }

        return null;
    }

    private function resolveWithoutAlias(Symbol $name, NodeEnvironment $env): ?GlobalVarNode
    {
        // Try to resolve in current namespace
        $def = $this->getDefinition($this->getNs(), $name);
        if ($def) {
            return new GlobalVarNode($env, $this->getNs(), $name, $def, $name->getStartLocation());
        }

        // Try to resolve in phel.core namespace
        $ns = 'phel\core';
        $def = $this->getDefinition($ns, $name);
        if ($def) {
            return new GlobalVarNode($env, $ns, $name, $def, $name->getStartLocation());
        }

        return null;
    }
}
