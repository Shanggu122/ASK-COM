<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_true_is_true(): void
    {
        $this->assertTrue(true);
    }

    public function test_array_key_and_value(): void
    {
        $row = ['id' => 1, 'name' => 'Alice'];
        $this->assertArrayHasKey('name', $row);
        $this->assertEquals('Alice', $row['name']);
    }

    public function test_string_contains_substring(): void
    {
        $s = 'Hello PHPUnit';
        $this->assertStringContainsString('PHPUnit', $s);
    }

    public function test_numbers_are_close(): void
    {
        $this->assertEqualsWithDelta(3.14, pi(), 0.01);
    }

    public function test_count_items(): void
    {
        $this->assertCount(3, [1, 2, 3]);
    }

    public function test_exception_thrown(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        throw new \InvalidArgumentException('test');
    }
}

