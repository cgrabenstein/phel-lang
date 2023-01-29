<?php

declare(strict_types=1);

namespace Phel\Lang;

use RuntimeException;

use function gettype;
use function is_float;
use function is_int;
use function is_object;
use function is_string;

/**
 * This Hasher is inspired by the Clojurescript implementation.
 */
class Hasher implements HasherInterface
{
    /**
     * @param mixed $value The value to hash
     *
     * @return int The hash of the given value
     */
    public function hash(mixed $value): int
    {
        if ($value instanceof HashableInterface) {
            return $value->hash();
        }

        if ($value === true) {
            return 1231; // Same hash value as in clojure
        }

        if ($value === false) {
            return 1237; // Same hash value as in clojure
        }

        if (is_string($value)) {
            return crc32($value);
        }

        if ($value === null) {
            return 0;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return $this->hashFloat($value);
        }

        if (is_object($value)) {
            return crc32(spl_object_hash($value));
        }

        throw new RuntimeException('This type is not hashable: ' . gettype($value));
    }

    private function hashFloat(float $value): int
    {
        if (is_finite($value)) {
            return (int)($value);
        }

        if ($value === INF) {
            return 2146435072; // Same hash value as in clojure
        }

        if ($value === -INF) {
            return -1048576; // Same hash value as in clojure
        }

        return 2146959360; // Same hash value as in clojure
    }
}
