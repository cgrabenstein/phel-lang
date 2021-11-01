<?php

declare(strict_types=1);

namespace PhelTest\Unit\Lang;

use InvalidArgumentException;
use Phel\Lang\PhelArray;
use PHPUnit\Framework\TestCase;

final class PhelArrayTest extends TestCase
{
    public function test_offset_set(): void
    {
        $arr = new PhelArray([1]);
        $arr[0] = 10;
        $this->assertEquals(10, $arr[0]);
    }

    public function test_offset_set_end(): void
    {
        $arr = new PhelArray([]);
        $arr[2] = 10;
        $this->assertEquals(null, $arr[0]);
        $this->assertEquals(null, $arr[1]);
        $this->assertEquals(10, $arr[2]);
    }


    public function test_offset_set_smaller_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $arr = new PhelArray([]);
        $arr[-1] = 10;
    }

    public function test_offset_exists(): void
    {
        $arr = new PhelArray([1]);

        $this->assertTrue(isset($arr[0]));
        $this->assertFalse(isset($arr[1]));
    }

    public function test_offset_unset(): void
    {
        $arr = new PhelArray([1, 2, 3]);

        unset($arr[1]);
        $this->assertEquals([1, 3], $arr->toPhpArray());
    }

    public function test_offset_unset_out_of_bound1(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $arr = new PhelArray([1, 2, 3]);
        unset($arr[-1]);
    }

    public function test_offset_unset_out_of_bound2(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $arr = new PhelArray([1, 2, 3]);
        unset($arr[3]);
    }

    public function test_offset_get(): void
    {
        $arr = new PhelArray([1]);
        $this->assertEquals(1, $arr[0]);
        $this->assertNull($arr[1]);
    }

    public function test_count(): void
    {
        $arr = new PhelArray([1]);
        $this->assertEquals(1, count($arr));
    }

    public function test_foreach(): void
    {
        $arr = new PhelArray([1, 2, 3]);

        $result = [];
        foreach ($arr as $x) {
            $result[] = $x;
        }

        $this->assertEquals([1, 2, 3], $result);
    }

    public function test_equals(): void
    {
        $arr1 = new PhelArray([1, 2, 3]);
        $arr2 = new PhelArray([1, 2, 3]);

        $this->assertTrue($arr1->equals($arr1));
        $this->assertTrue($arr1->equals($arr2));
        $this->assertTrue($arr2->equals($arr1));
    }

    public function test_not_equals(): void
    {
        $arr1 = new PhelArray([1, 2, 3]);
        $arr2 = new PhelArray([]);

        $this->assertFalse($arr1->equals($arr2));
        $this->assertFalse($arr2->equals($arr1));
    }

    public function test_not_equals_when_other_instance_is_being_compared(): void
    {
        $class = new class () {
            public array $data = [1,2,3];
        };

        $arr1 = new PhelArray([1, 2, 3]);

        self::assertFalse($arr1->equals($class));
    }

    public function test_hash(): void
    {
        $arr = new PhelArray([1, 2, 3]);
        $this->assertEquals(crc32(spl_object_hash($arr)), $arr->hash());
    }

    public function test_slice(): void
    {
        $arr = new PhelArray([1, 2, 3]);
        $this->assertEquals(new PhelArray([2, 3]), $arr->slice(1));
        $this->assertEquals(new PhelArray([2]), $arr->slice(1, 1));
    }

    public function test_cons(): void
    {
        $arr = new PhelArray([1, 2, 3]);
        $this->assertEquals(new PhelArray([0, 1, 2, 3]), $arr->cons(0));
    }

    public function test_to_php_array(): void
    {
        $this->assertEquals([1, 2, 3], (new PhelArray([1, 2, 3]))->toPhpArray());
    }

    public function test_first(): void
    {
        $this->assertEquals(1, (new PhelArray([1, 2, 3]))->first());
        $this->assertNull((new PhelArray([]))->first());
    }

    public function test_cdr(): void
    {
        $this->assertEquals(new PhelArray([2, 3]), (new PhelArray([1, 2, 3]))->cdr());
        $this->assertNull((new PhelArray([]))->cdr());
    }

    public function test_rest(): void
    {
        $this->assertEquals(new PhelArray([2, 3]), (new PhelArray([1, 2, 3]))->rest());
        $this->assertEquals(new PhelArray([]), (new PhelArray([]))->rest());
    }

    public function test_pop(): void
    {
        $arr = new PhelArray([1, 2, 3]);
        $x = $arr->pop();
        $this->assertEquals(3, $x);
        $this->assertEquals(new PhelArray([1, 2]), $arr);
    }

    public function test_remove(): void
    {
        $arr = new PhelArray([1, 2, 3]);
        $this->assertEquals(new PhelArray([2]), $arr->remove(1, 1));
        $this->assertEquals(new PhelArray([3]), $arr->remove(1));
    }

    public function test_push(): void
    {
        $arr = new PhelArray([]);
        $this->assertEquals(new PhelArray([1]), $arr->push(1));
    }

    public function test_concat(): void
    {
        $arr1 = new PhelArray([1, 2]);
        $arr2 = new PhelArray([3, 4]);

        $arr1->concat($arr2);

        $this->assertEquals(new PhelArray([1, 2, 3, 4]), $arr1);
        $this->assertEquals(new PhelArray([3, 4]), $arr2);
    }

    public function test_to_string(): void
    {
        $this->assertEquals('@[1 2 3]', (new PhelArray([1, 2, 3]))->__toString());
    }
}
