<?php

declare(strict_types=1);

namespace Phel\Api\Domain;

use Phel\Api\Infrastructure\PhelFnLoaderInterface;
use Phel\Api\Transfer\PhelFunction;
use Phel\Lang\Keyword;

final class PhelFnNormalizer implements PhelFnNormalizerInterface
{
    public function __construct(
        private PhelFnLoaderInterface $phelFnLoader,
    ) {
    }

    /**
     * @param list<string> $namespaces
     *
     * @return list<PhelFunction>
     */
    public function getPhelFunctions(array $namespaces = []): array
    {
        $normalizedData = $this->phelFnLoader->getNormalizedPhelFunctions($namespaces);

        $result = [];
        foreach ($normalizedData as $fnName => $meta) {
            $isPrivate = $meta[Keyword::create('private')] ?? false;
            if ($isPrivate) {
                continue;
            }

            $doc = $meta[Keyword::create('doc')] ?? '';
            $pattern = '#(```phel\n(?<fnSignature>.*)\n```\n)?(?<desc>.*)#s';
            preg_match($pattern, $doc, $matches);
            $groupKey = $this->groupKey($fnName);

            $result[$groupKey][] = new PhelFunction(
                $fnName,
                $doc,
                $matches['fnSignature'] ?? '',
                $matches['desc'] ?? '',
                $groupKey,
            );
        }

        foreach ($result as $values) {
            usort($values, $this->sortingPhelFunctionsCallback());
        }

        return array_merge(...array_values($result));
    }

    private function groupKey(string $fnName): string
    {
        $key = preg_replace(
            '/[^a-zA-Z0-9\-]+/',
            '',
            str_replace('/', '-', $fnName),
        );

        return strtolower(rtrim($key, '-'));
    }

    private function sortingPhelFunctionsCallback(): callable
    {
        return static function (PhelFunction $a, PhelFunction $b): int {
            return $a->fnName() <=> $b->fnName();
        };
    }
}
