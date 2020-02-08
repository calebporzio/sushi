<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;

class SushiTest extends TestCase
{
    public $cachePath;

    public function setUp(): void
    {
        parent::setUp();

        config(['sushi.cache-path' => $this->cachePath = __DIR__.'/cache']);

        Foo::resetStatics();
        Bar::resetStatics();
        File::cleanDirectory($this->cachePath);
    }

    public function tearDown(): void
    {
        Foo::resetStatics();
        Bar::resetStatics();
        File::cleanDirectory($this->cachePath);

        parent::tearDown();
    }

    /** @test */
    function basic_usage()
    {
        $this->assertEquals(2, Foo::count());
        $this->assertEquals('bar', Foo::first()->foo);
        $this->assertEquals('lob', Foo::whereBob('lob')->first()->bob);
        $this->assertEquals(2, Bar::count());
        $this->assertEquals('bar', Bar::first()->foo);
        $this->assertEquals('lob', Bar::whereBob('lob')->first()->bob);
    }

    /** @test */
    function models_using_the_get_rows_property_arent_cached()
    {
        Bar::$hasBeenAccessedBefore = false;
        $this->assertEquals(2, Bar::count());
        Bar::resetStatics();
        $this->assertEquals(3, Bar::count());
    }

    /** @test */
    function uses_in_memory_if_the_cache_directory_is_not_writeable_or_not_found()
    {
        config(['sushi.cache-path' => $path = __DIR__.'/non-existant-path']);

        Foo::count();

        $this->assertFalse(file_exists($path));
        $this->assertEquals(':memory:', (new Foo)->getConnection()->getDatabaseName());
    }

    /** @test */
    function caches_sqlite_file_if_storage_cache_folder_is_available()
    {
        Foo::count();

        $this->assertTrue(file_exists($this->cachePath));
        $this->assertStringContainsString(
            'sushi/tests/cache/sushi-tests-foo.sqlite',
            (new Foo)->getConnection()->getDatabaseName()
        );
    }

    /** @test */
    function uses_same_cache_between_requests()
    {
        $this->markTestSkipped("I can't find a good way to test this right now.");
    }

    /** @test */
    function use_same_cache_between_requests()
    {
        $this->markTestSkipped("I can't find a good way to test this right now.");
    }

    /** @test */
    function adds_primary_key_if_needed()
    {
        $this->assertEquals(1, Foo::find(1)->getKey());
    }

    /** @test */
    public function can_be_used_in_a_test_environment()
    {
        $this->assertEquals([
            ['id' => 1, 'foo' => 'bar', 'bob' => 'lob'],
            ['id' => 2, 'foo' => 'bar', 'bob' => 'lob'],
            ['id' => 3, 'foo' => 'baz', 'bob' => 'law'],
        ], Foo::all()->toArray());

        Foo::test([
            ['name' => 'foo'],
            ['name' => 'bar'],
        ]);

        $this->assertEquals([
            ['id' => 1, 'name' => 'foo'],
            ['id' => 2, 'name' => 'bar'],
        ], Foo::all()->toArray());

        Foo::test([
            ['name' => 'baz'],
            ['name' => 'naz'],
        ]);

        Baz::test([
            ['name' => 'wut'],
            ['name' => 'tit'],
        ]);

        $this->assertEquals([
            ['id' => 1, 'name' => 'baz'],
            ['id' => 2, 'name' => 'naz'],
        ], Foo::all()->toArray());

        $this->assertEquals([
            ['id' => 1, 'name' => 'wut'],
            ['id' => 2, 'name' => 'tit'],
        ], Baz::all()->toArray());
    }
}

class Foo extends Model
{
    use \Sushi\Sushi;

    protected $rows = [
        ['foo' => 'bar', 'bob' => 'lob'],
        ['foo' => 'bar', 'bob' => 'lob'],
        ['foo' => 'baz', 'bob' => 'law'],
    ];

    public static function resetStatics()
    {
        static::setSushiConnection(null);
        static::clearBootedModels();
        static::$testRows = null;
    }

    public static function setSushiConnection($connection)
    {
        static::$sushiConnection = $connection;
    }
}

class Bar extends Model
{
    use \Sushi\Sushi;

    public static $hasBeenAccessedBefore = false;

    public function getRows()
    {
        if (static::$hasBeenAccessedBefore) {
            return [
                ['foo' => 'bar', 'bob' => 'lob'],
                ['foo' => 'baz', 'bob' => 'law'],
                ['foo' => 'baz', 'bob' => 'law'],
            ];
        } else {
            static::$hasBeenAccessedBefore = true;

            return [
                ['foo' => 'bar', 'bob' => 'lob'],
                ['foo' => 'baz', 'bob' => 'law'],
            ];
        }
    }

    public static function resetStatics()
    {
        static::setSushiConnection(null);
        static::clearBootedModels();
        static::$testRows = null;
    }

    public static function setSushiConnection($connection)
    {
        static::$sushiConnection = $connection;
    }
}

class Baz extends Model
{
    use \Sushi\Sushi;
}
