<?php

declare(strict_types=1);

namespace Phel\Lang;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use Iterator;
use Phel\Printer\Printer;

/**
 * @deprecated in favor of PersistentList|PersistentVector
 * @template T
 * @template-implements ArrayAccess<int, T>
 * @template-implements Iterator<int, T>
 * @template-implements SeqInterface<T, PhelArray>
 */
final class PhelArray extends AbstractType implements
    ArrayAccess,
    Countable,
    Iterator,
    ConsInterface,
    SliceInterface,
    SeqInterface,
    PopInterface,
    RemoveInterface,
    PushInterface,
    ConcatInterface
{
    use MetaTrait;
    use IteratorComparatorTrait;

    /** @var mixed[] */
    private array $data;

    /**
     * @param mixed[] $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Create a new Phel array from a list of value.
     *
     * @param mixed[] $values
     */
    public static function create(...$values): PhelArray
    {
        return new PhelArray($values);
    }

    public function offsetSet($offset, $value): void
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Offset must be bigger or equal zero. Given: ' . $offset);
        }

        if ($offset < count($this->data)) {
            $this->data[$offset] = $value;
        } else {
            for ($i = count($this->data); $i < $offset; $i++) {
                $this->data[$i] = null;
            }

            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset): void
    {
        if ($offset < 0 || $offset >= count($this->data)) {
            throw new InvalidArgumentException('Index out of bounds: ' . $offset . ' [0,' . count($this->data) . ')');
        }

        unset($this->data[$offset]);
        $this->data = array_values($this->data); // reindex
    }

    /**
     * @param int $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function count(): int
    {
        return count($this->data);
    }

    /**
     * @return mixed
     */
    public function current(): mixed
    {
        return current($this->data);
    }

    public function key(): mixed
    {
        return (int) key($this->data);
    }

    public function next(): void
    {
        next($this->data);
    }

    public function rewind(): void
    {
        reset($this->data);
    }

    public function valid(): bool
    {
        return key($this->data) !== null;
    }

    public function hash(): int
    {
        return crc32(spl_object_hash($this));
    }

    public function equals($other): bool
    {
        // Should be the same type
        if (!($other instanceof PhelArray)) {
            return false;
        }

        // Should have the same length
        if (count($this) !== count($other)) {
            return false;
        }

        return $this->hasSameKeysAndValues($other);
    }

    public function slice(int $offset = 0, ?int $length = null): SliceInterface
    {
        return new PhelArray(array_slice($this->data, $offset, $length));
    }

    public function cons($x): ConsInterface
    {
        array_unshift($this->data, $x);
        return $this;
    }

    public function toPhpArray(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function first()
    {
        if (count($this->data) > 0) {
            return $this->data[0];
        }

        return null;
    }

    public function cdr()
    {
        if ($this->count() <= 1) {
            return null;
        }

        return new PhelArray(array_slice($this->data, 1));
    }

    public function rest()
    {
        return new PhelArray(array_slice($this->data, 1));
    }

    public function pop()
    {
        return array_pop($this->data);
    }

    public function remove(int $offset, ?int $length = null): RemoveInterface
    {
        $length = $length ?? count($this);

        return new PhelArray(array_splice($this->data, $offset, $length));
    }

    public function push($x): PushInterface
    {
        $this->data[] = $x;
        return $this;
    }

    public function concat($xs): ConcatInterface
    {
        /** @var mixed $x */
        foreach ($xs as $x) {
            $this->data[] = $x;
        }
        return $this;
    }

    public function __toString(): string
    {
        return Printer::readable()->print($this);
    }
}
