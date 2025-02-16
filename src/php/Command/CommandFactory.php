<?php

declare(strict_types=1);

namespace Phel\Command;

use Gacela\Framework\AbstractFactory;
use Gacela\Framework\Config\ConfigReader\PhpConfigReader;
use Phel\Command\Domain\Finder\ComposerVendorDirectoriesFinder;
use Phel\Command\Domain\Finder\DirectoryFinder;
use Phel\Command\Domain\Finder\DirectoryFinderInterface;
use Phel\Command\Domain\Finder\VendorDirectoriesFinderInterface;
use Phel\Command\Domain\Shared\CommandExceptionWriter;
use Phel\Command\Domain\Shared\CommandExceptionWriterInterface;
use Phel\Command\Domain\Shared\Exceptions\ExceptionArgsPrinter;
use Phel\Command\Domain\Shared\Exceptions\ExceptionPrinterInterface;
use Phel\Command\Domain\Shared\Exceptions\Extractor\FilePositionExtractor;
use Phel\Command\Domain\Shared\Exceptions\Extractor\SourceMapExtractor;
use Phel\Command\Domain\Shared\Exceptions\TextExceptionPrinter;
use Phel\Compiler\Infrastructure\Munge;
use Phel\Printer\Printer;
use Phel\Run\Domain\Repl\ColorStyle;

/**
 * @method CommandConfig getConfig()
 */
final class CommandFactory extends AbstractFactory
{
    public function createCommandExceptionWriter(): CommandExceptionWriterInterface
    {
        return new CommandExceptionWriter(
            $this->createExceptionPrinter(),
        );
    }

    public function createExceptionPrinter(): ExceptionPrinterInterface
    {
        return new TextExceptionPrinter(
            new ExceptionArgsPrinter(Printer::readable()),
            ColorStyle::withStyles(),
            new Munge(),
            new FilePositionExtractor(new SourceMapExtractor()),
        );
    }

    public function createDirectoryFinder(): DirectoryFinderInterface
    {
        return new DirectoryFinder(
            $this->getConfig()->getAppRootDir(),
            $this->getConfig()->getCodeDirs(),
            $this->createComposerVendorDirectoriesFinder(),
        );
    }

    public function getPhpConfigReader(): PhpConfigReader
    {
        return $this->getProvidedDependency(CommandDependencyProvider::PHP_CONFIG_READER);
    }

    private function createComposerVendorDirectoriesFinder(): VendorDirectoriesFinderInterface
    {
        return new ComposerVendorDirectoriesFinder(
            $this->getConfig()->getAppRootDir() . '/' . $this->getConfig()->getVendorDir(),
        );
    }
}
