<?php

declare(strict_types=1);

namespace Phel\Formatter\Domain;

use Phel\Command\CommandFacadeInterface;
use Phel\Compiler\Lexer\Exceptions\LexerValueException;
use Phel\Compiler\Parser\Exceptions\AbstractParserException;
use Phel\Formatter\Domain\Exception\FilePathException;
use Phel\Formatter\Domain\Rules\Zipper\ZipperException;
use Phel\Formatter\Infrastructure\IO\FileIoInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class PathsFormatter
{
    private CommandFacadeInterface $commandFacade;
    private FormatterInterface $formatter;
    private PathFilterInterface $pathFilter;
    private FileIoInterface $fileIo;

    public function __construct(
        CommandFacadeInterface $commandFacade,
        FormatterInterface $formatter,
        PathFilterInterface $pathFilter,
        FileIoInterface $fileIo
    ) {
        $this->commandFacade = $commandFacade;
        $this->formatter = $formatter;
        $this->pathFilter = $pathFilter;
        $this->fileIo = $fileIo;
    }

    /**
     * @return list<string> successful formatted file paths
     */
    public function format(array $paths, OutputInterface $output): array
    {
        $formattedFilePaths = [];

        foreach ($this->pathFilter->filterPaths($paths) as $path) {
            try {
                $wasFormatted = $this->formatFile($path);
                if ($wasFormatted) {
                    $formattedFilePaths[] = $path;
                }
            } catch (AbstractParserException $e) {
                $this->commandFacade->writeLocatedException($output, $e, $e->getCodeSnippet());
            } catch (Throwable $e) {
                $this->commandFacade->writeStackTrace($output, $e);
            }
        }

        return $formattedFilePaths;
    }

    /**
     * @throws LexerValueException
     * @throws ZipperException
     * @throws AbstractParserException
     *
     * @return bool True if the file was formatted. False if the file wasn't altered because it was already formatted.
     */
    private function formatFile(string $filename): bool
    {
        if (is_dir($filename)) {
            throw FilePathException::directoryFound($filename);
        }

        if (!is_file($filename)) {
            throw FilePathException::notFound($filename);
        }

        $code = $this->fileIo->getContents($filename);
        $formattedCode = $this->formatter->format($code, $filename);
        $this->fileIo->putContents($filename, $formattedCode);

        return (bool)strcmp($formattedCode, $code);
    }
}
