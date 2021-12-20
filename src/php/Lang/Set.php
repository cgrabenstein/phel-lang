<?php

declare(strict_types=1);

namespace Phel\Lang;

use Countable;
use Iterator;
use Phel\Printer\Printer;

/**
 * @deprecated in favor of PersistentHashSet
 * @template T
 * @template-implements SeqInterface<T, PhelArray>
 */
final class Set extends AbstractType implements
    Countable,
    Iterator,
    SeqInterface,
    ConsInterface,
    PushInterface,
    ConcatInterface
{
    use MetaTrait;
    /** @var mixed[] */
    private array $data = [];
    private int $currentIndex = 0;

    /**
     * @param mixed[] $data
     */
    public function __construct(array $data)
    {
        $this->data = [];
        $this->concat($data);
    }

    public function hash(): int
    {
        return crc32(spl_object_hash($this));
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function current(): mixed
    {
        return current($this->data);
    }

    public function next(): void
    {
        $this->currentIndex++;
        next($this->data);
    }

    public function key(): mixed
    {
        return $this->currentIndex;
    }

    public function valid(): bool
    {
        $result = key($this->data) !== null;

        if (!$result) {
            $this->currentIndex = 0;
        }

        return $result;
    }

    public function rewind(): void
    {
        $this->currentIndex = 0;
        reset($this->data);
    }

    public function cons($x): ConsInterface
    {
        $this->push($x);
        return $this;
    }

    public function first()
    {
        if (empty($this->data)) {
            return null;
        }

        $this->rewind();
        return $this->current();
    }

    public function cdr()
    {
        if ($this->count() <= 1) {
            return null;
        }

        return new PhelArray(array_values(array_slice($this->data, 1)));
    }

    public function rest()
    {
        $this->rewind();
        $this->next();

        return new PhelArray(array_values(array_slice($this->data, 1)));
    }

    public function push($x): PushInterface
    {
        $hash = $this->offsetHash($x);
        $this->data[$hash] = $x; // Don't need to check if $x is already there, just override.

        return $this;
    }

    public function concat($xs): ConcatInterface
    {
        foreach ($xs as $x) {
            $this->push($x);
        }

        return $this;
    }

    public function intersection(Set $set): Set
    {
        return new Set(array_intersect_key($this->data, $set->data));
    }

    public function difference(Set $set): Set
    {
        return new Set(array_diff_key($this->data, $set->data));
    }

    public function equals($other): bool
    {
        // Should be the same type
        if (!($other instanceof Set)) {
            return false;
        }

        // Should have the same length
        if (count($this) !== count($other)) {
            return false;
        }

        return $this->data == $other->data;
    }

    public function toPhpArray(): array
    {
        return array_values($this->data);
    }

    public function toArray(): array
    {
        return $this->toPhpArray();
    }

    /**
     * Creates a hash for the given key.
     *
     * @param mixed $offset The access key of the Set
     */
    private function offsetHash($offset): int
    {
        if ($offset instanceof TypeInterface) {
            return $offset->hash();
        }

        if (is_object($offset)) {
            return crc32(spl_object_hash($offset));
        }

        return crc32((string) $offset);
    }

    public function __toString(): string
    {
        return Printer::readable()->print($this);
    }
}
