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
        File::cleanDirectory($this->cachePath);
    }

    public function tearDown(): void
    {
        Foo::resetStatics();
        File::cleanDirectory($this->cachePath);

        parent::tearDown();
    }

    /** @test */
    function basic_usage()
    {
        $this->assertEquals(2, Foo::count());
        $this->assertEquals('bar', Foo::first()->foo);
        $this->assertEquals('lob', Foo::whereBob('lob')->first()->bob);
    }

    /** @test */
    function not_adding_rows_property_throws_an_error()
    {
        $this->expectExceptionMessage('Sushi: $rows property not found on model: Tests\Bar');

        Bar::count();
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
        $this->markTestSkipped('I can\' find a good way to test this right now');
    }

    /** @test */
    function use_same_cache_between_requests()
    {
        $this->markTestSkipped('I can\' find a good way to test this right now');
    }

    /** @test */
    function adds_primary_key_if_needed()
    {
        $this->assertEquals(1, Foo::find(1)->getKey());
    }

    /** @test */
    public function it_uses_the_expected_column_types_for_the_schema()
    {
        $baz = (new Baz)->first();

        $columns = collect($baz->getConnection()->select("pragma table_info({$baz->getTable()})"));

        $name = $columns->firstWhere('name', 'name');
        $this->assertEquals('varchar', $name->type);

        $age = $columns->firstWhere('name', 'age');
        $this->assertEquals('integer', $age->type);

        $price = $columns->firstWhere('name', 'price');
        $this->assertEquals('float', $price->type);
    }
}

class Foo extends Model
{
    use \Sushi\Sushi;

    protected $rows = [
        ['foo' => 'bar', 'bob' => 'lob'],
        ['foo' => 'baz', 'bob' => 'law'],
    ];

    public static function resetStatics()
    {
        static::setSushiConnection(null);
        static::clearBootedModels();
    }

    public static function setSushiConnection($connection)
    {
        static::$sushiConnection = $connection;
    }
}

class Bar extends Model
{
    use \Sushi\Sushi;
}

class Baz extends Model
{
    use \Sushi\Sushi;

    protected $rows = [
        [
            'name' => 'foobar',
            'age' => 10,
            'price' => 9.99,
        ],
    ];
}
