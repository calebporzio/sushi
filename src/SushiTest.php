<?php

namespace Sushi;

use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase;

class SushiTest extends TestCase
{
    /** @test */
    function basic_usage()
    {
        $this->assertEquals(2, Foo::count());
        $this->assertEquals('bar', Foo::first()->foo);
        $this->assertEquals('lob', Foo::whereBob('lob')->first()->bob);
    }
}

class Foo extends Model
{
    use Sushi;

    protected $rows = [
        ['foo' => 'bar', 'bob' => 'lob'],
        ['foo' => 'baz', 'bob' => 'law'],
    ];
}
