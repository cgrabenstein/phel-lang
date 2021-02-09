<?php

declare(strict_types=1);

namespace Phel\Command\Export;

use Phel\Command\Shared\CommandIoInterface;
use Phel\Interop\Generator\WrapperGeneratorInterface;
use Phel\Interop\ReadModel\Wrapper;

final class ExportCommand
{
    public const COMMAND_NAME = 'export';

    private WrapperGeneratorInterface $wrapperGenerator;
    private CommandIoInterface $io;
    private FunctionsToExportFinderInterface $functionsToExportFinder;
    private DirectoryRemoverInterface $directoryRemover;

    public function __construct(
        WrapperGeneratorInterface $wrapperGenerator,
        CommandIoInterface $io,
        FunctionsToExportFinderInterface $functionsToExportFinder,
        DirectoryRemoverInterface $directoryRemover
    ) {
        $this->wrapperGenerator = $wrapperGenerator;
        $this->io = $io;
        $this->functionsToExportFinder = $functionsToExportFinder;
        $this->directoryRemover = $directoryRemover;
    }

    public function run(): void
    {
        $wrappers = [];
        foreach ($this->functionsToExportFinder->findInPaths() as $ns => $functionsToExport) {
            $wrappers[] = $this->wrapperGenerator->generateCompiledPhp($ns, $functionsToExport);
        }

        if (empty($wrappers)) {
            $this->io->writeln('No functions were found to be exported.');
            return;
        }

        $this->writeGeneratedWrappers($wrappers);
    }

    /**
     * @param list<Wrapper> $wrappers
     */
    private function writeGeneratedWrappers(array $wrappers): void
    {
        $this->io->writeln('Exported namespaces:');

        $first = reset($wrappers);
        $this->directoryRemover->removeDir($first->destinationDir());

        foreach ($wrappers as $i => $wrapper) {
            if (!is_dir($wrapper->dir())) {
                mkdir($wrapper->dir(), 0777, true);
            }

            file_put_contents($wrapper->absolutePath(), $wrapper->compiledPhp());
            $this->io->writeln(sprintf('  %d) %s', $i + 1, $wrapper->absolutePath()));
        }
    }
}
