<?php

declare(strict_types=1);

namespace Phel\Command\Domain\Shared\Exceptions;

use Phel\Command\Domain\Shared\Exceptions\Extractor\FilePositionExtractorInterface;
use Phel\Compiler\Domain\Emitter\OutputEmitter\MungeInterface;
use Phel\Compiler\Domain\Exceptions\AbstractLocatedException;
use Phel\Compiler\Domain\Parser\ReadModel\CodeSnippet;
use Phel\Lang\FnInterface;
use Phel\Lang\SourceLocation;
use Phel\Run\Domain\Repl\ColorStyleInterface;
use ReflectionClass;
use Throwable;

use function get_class;
use function strlen;

final class TextExceptionPrinter implements ExceptionPrinterInterface
{
    public function __construct(
        private ExceptionArgsPrinterInterface $exceptionArgsPrinter,
        private ColorStyleInterface $style,
        private MungeInterface $munge,
        private FilePositionExtractorInterface $filePositionExtractor,
    ) {
    }

    public function printException(AbstractLocatedException $e, CodeSnippet $codeSnippet): void
    {
        echo $this->getExceptionString($e, $codeSnippet);
    }

    public function getExceptionString(AbstractLocatedException $e, CodeSnippet $codeSnippet): string
    {
        $str = '';
        $errorStartLocation = $e->getStartLocation() ?? $codeSnippet->getStartLocation();
        $errorEndLocation = $e->getEndLocation() ?? $codeSnippet->getEndLocation();
        $errorFirstLine = $errorStartLocation->getLine();
        $codeFirstLine = $codeSnippet->getStartLocation()->getLine();

        $str .= $this->style->blue($e->getMessage()) . PHP_EOL;
        $str .= 'in ' . $errorStartLocation->getFile() . ':' . $errorFirstLine . PHP_EOL . PHP_EOL;

        $lines = explode(PHP_EOL, $codeSnippet->getCode());
        $endLineLength = strlen((string)$codeSnippet->getEndLocation()->getLine());
        $padLength = $endLineLength - strlen((string)$codeFirstLine);

        foreach ($lines as $index => $line) {
            $str .= str_pad((string)($codeFirstLine + $index), $padLength, ' ', STR_PAD_LEFT);
            if ($line !== '') {
                $str .= '| ' . $line . PHP_EOL;
            } else {
                $str .= '|' . PHP_EOL;
            }

            $eStartLine = $errorStartLocation->getLine();
            if ($eStartLine === $errorEndLocation->getLine()
                && $eStartLine === $index + $codeSnippet->getStartLocation()->getLine()
            ) {
                $str .= $this->underliningErrorPointer($endLineLength, $errorStartLocation, $errorEndLocation);
            }
        }

        if ($e->getPrevious()) {
            $str .= PHP_EOL . PHP_EOL . 'Caused by:' . PHP_EOL;
            $str .= $e->getPrevious()->getTraceAsString();
            $str .= PHP_EOL;
        }

        return $str;
    }

    public function printStackTrace(Throwable $e): void
    {
        echo $this->getStackTraceString($e);
    }

    public function getStackTraceString(Throwable $e): string
    {
        $str = '';
        $type = get_class($e);
        $msg = $e->getMessage();
        $errorFile = $e->getFile();
        $errorLine = $e->getLine();
        $pos = $this->filePositionExtractor->getOriginal($errorFile, $errorLine);

        $str .= $this->style->blue("{$type}: {$msg}" . PHP_EOL);
        $str .= "in {$pos->filename()}:{$pos->line()} (gen: {$errorFile}:{$errorLine})" . PHP_EOL . PHP_EOL;

        foreach ($e->getTrace() as $i => $frame) {
            $class = $frame['class'] ?? null;
            $file = $frame['file'] ?? 'unknown_file';
            $line = $frame['line'] ?? 0;

            if ($class) {
                $rf = new ReflectionClass($class);
                if ($rf->implementsInterface(FnInterface::class)) {
                    $boundTo = $rf->getConstant('BOUND_TO');
                    $fnName = $boundTo !== false ? $this->munge->decodeNs($boundTo) : '__invoke';
                    $argString = $this->exceptionArgsPrinter->parseArgsAsString($frame['args'] ?? []);
                    $pos = $this->filePositionExtractor->getOriginal($file, $line);
                    $str .= "#{$i} {$pos->filename()}:{$pos->line()} (gen: {$file}:{$line}) : ({$fnName}{$argString})" . PHP_EOL;

                    continue;
                }
            }

            $class = $class ?? '';
            $type = $frame['type'] ?? '';
            $fn = $frame['function'] ?? '';
            $argString = $this->exceptionArgsPrinter->buildPhpArgsString($frame['args'] ?? []);
            $str .= "#{$i} {$file}({$line}): {$class}{$type}{$fn}({$argString})" . PHP_EOL;
        }

        return $str;
    }

    private function underliningErrorPointer(int $lineLength, SourceLocation $start, SourceLocation $end): string
    {
        $preEmptyLines = str_repeat(' ', $lineLength + 2 + $start->getColumn());
        $pointer = str_repeat('^', max(1, $end->getColumn() - $start->getColumn()));
        $pointerInRed = $this->style->red($pointer);

        return $preEmptyLines . $pointerInRed . PHP_EOL;
    }
}
