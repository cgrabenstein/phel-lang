<?php

declare(strict_types=1);

namespace Phel\Build\Domain\Extractor;

use Phel\Build\Domain\IO\FileIoInterface;
use Phel\Compiler\CompilerFacadeInterface;
use Phel\Compiler\Domain\Analyzer\Ast\NsNode;
use Phel\Compiler\Domain\Analyzer\Environment\NodeEnvironment;
use Phel\Compiler\Domain\Lexer\Exceptions\LexerValueException;
use Phel\Compiler\Domain\Parser\Exceptions\AbstractParserException;
use Phel\Compiler\Domain\Parser\ParserNode\TriviaNodeInterface;
use Phel\Compiler\Domain\Reader\Exceptions\ReaderException;
use Phel\Lang\Symbol;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use RuntimeException;

final class NamespaceExtractor implements NamespaceExtractorInterface
{
    public function __construct(
        private CompilerFacadeInterface $compilerFacade,
        private NamespaceSorterInterface $namespaceSorter,
        private FileIoInterface $fileIo,
    ) {
    }

    /**
     * @throws ExtractorException
     * @throws LexerValueException
     */
    public function getNamespaceFromFile(string $path): NamespaceInformation
    {
        $content = $this->fileIo->getContents($path);

        try {
            $tokenStream = $this->compilerFacade->lexString($content);
            do {
                $parseTree = $this->compilerFacade->parseNext($tokenStream);
            } while ($parseTree instanceof TriviaNodeInterface);

            if (!$parseTree) {
                throw ExtractorException::cannotReadFile($path);
            }

            $readerResult = $this->compilerFacade->read($parseTree);
            $ast = $readerResult->getAst();
            $node = $this->compilerFacade->analyze($ast, NodeEnvironment::empty());

            if ($node instanceof NsNode) {
                return new NamespaceInformation(
                    realpath($path),
                    $node->getNamespace(),
                    array_map(
                        static fn (Symbol $s) => $s->getFullName(),
                        $node->getRequireNs(),
                    ),
                );
            }

            throw ExtractorException::cannotExtractNamespaceFromPath($path);
        } catch (AbstractParserException|ReaderException) {
            throw ExtractorException::cannotParseFile($path);
        }
    }

    /**
     * @param list<string> $directories
     *
     * @throws ExtractorException
     *
     * @return list<NamespaceInformation>
     */
    public function getNamespacesFromDirectories(array $directories): array
    {
        /** @var list<list<NamespaceInformation>> $namespaces */
        $namespaces = [];
        foreach ($directories as $directory) {
            $allNamespacesInDir = $this->findAllNs($directory);
            $namespaces[] = $allNamespacesInDir;
        }

        // Combine all nested namespaces and check for duplicates
        /** @var list<NamespaceInformation> $result */
        $result = [];
        /** @var array<string, NamespaceInformation> $seen */
        $seen = [];
        foreach ($namespaces as $namespaceInformationList) {
            foreach ($namespaceInformationList as $info) {
                if (isset($seen[$info->getNamespace()])) {
                    $firstFile = $seen[$info->getNamespace()]->getFile();
                    $secondFile = $info->getFile();
                    $namespace = $info->getNamespace();
                    throw ExtractorException::duplicateNamespace($namespace, $firstFile, $secondFile);
                }

                $result[] = $info;
                $seen[$info->getNamespace()] = $info;
            }
        }

        return $this->sortNamespaceInformationList($result);
    }

    /**
     * @param list<NamespaceInformation> $namespaceInformationList
     *
     * @return list<NamespaceInformation>
     */
    private function sortNamespaceInformationList(array $namespaceInformationList): array
    {
        $dependencyIndex = [];
        $infoIndex = [];
        foreach ($namespaceInformationList as $info) {
            $dependencyIndex[$info->getNamespace()] = $info->getDependencies();
            $infoIndex[$info->getNamespace()] = $info;
        }

        $orderedNamespaces = $this->namespaceSorter->sort(array_keys($dependencyIndex), $dependencyIndex);

        $result = [];
        foreach ($orderedNamespaces as $namespace) {
            if (isset($infoIndex[$namespace])) {
                $result[] = $infoIndex[$namespace];
            }
        }

        return $result;
    }

    /**
     * @throws ExtractorException
     */
    private function findAllNs(string $directory): array
    {
        $realpath = realpath($directory);
        if (!$realpath) {
            throw new RuntimeException("Directory '{$directory}' not found");
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($realpath));
        $phelIterator = new RegexIterator($iterator, '/^.+\.phel$/i', RegexIterator::GET_MATCH);

        return array_map(
            fn ($file) => $this->getNamespaceFromFile($file[0]),
            iterator_to_array($phelIterator),
        );
    }
}
