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

        if (! file_exists($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }

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
        $this->assertEquals(3, Foo::count());
        $this->assertEquals('bar', Foo::first()->foo);
        $this->assertEquals('lob', Foo::whereBob('lob')->first()->bob);
        $this->assertEquals(2, Bar::count());
        $this->assertEquals('bar', Bar::first()->foo);
        $this->assertEquals('lob', Bar::whereBob('lob')->first()->bob);
    }

    /** @test */
    function columns_with_varying_types()
    {
        $row = ModelWithVaryingTypeColumns::first();
        $connectionBuilder = ModelWithVaryingTypeColumns::resolveConnection()->getSchemaBuilder();
        $this->assertEquals('integer', $connectionBuilder->getColumnType('model_with_varying_type_columns', 'int'));
        $this->assertEquals('float', $connectionBuilder->getColumnType('model_with_varying_type_columns', 'float'));
        $this->assertEquals('datetime', $connectionBuilder->getColumnType('model_with_varying_type_columns', 'dateTime'));
        $this->assertEquals('string', $connectionBuilder->getColumnType('model_with_varying_type_columns', 'string'));
        $this->assertEquals(null, $row->null);
    }

    /** @test */
    function model_with_custom_schema()
    {
        ModelWithCustomSchema::count();
        $connectionBuilder = ModelWithCustomSchema::resolveConnection()->getSchemaBuilder();
        $this->assertEquals('string', $connectionBuilder->getColumnType('model_with_custom_schemas', 'float'));
        $this->assertEquals('string', $connectionBuilder->getColumnType('model_with_custom_schemas', 'string'));
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
            str_replace('\\', '/', (new Foo())->getConnection()->getDatabaseName())
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
        $this->assertEquals([5,6], ModelWithNonStandardKeys::orderBy('id')->pluck('id')->toArray());
        $this->assertEquals(1, Foo::find(1)->getKey());
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
    }

    public static function setSushiConnection($connection)
    {
        static::$sushiConnection = $connection;
    }
}

class ModelWithNonStandardKeys extends Model
{
    use \Sushi\Sushi;

    protected $rows = [
        ['id' => 5, 'foo' => 'bar', 'bob' => 'lob'],
        ['id' => 6, 'foo' => 'baz', 'bob' => 'law'],
    ];
}

class ModelWithVaryingTypeColumns extends Model
{
    use \Sushi\Sushi;

    public function getRows() {
        return [[
            'int' => 123,
            'float' => 123.456,
            'datetime' => \Carbon\Carbon::parse('January 1 2020'),
            'string' => 'bar',
            'null' => null,
        ]];
    }
}

class ModelWithCustomSchema extends Model
{
    use \Sushi\Sushi;

    protected $rows = [[
        'float' => 123.456,
        'string' => 'foo',
    ]];

    protected $schema = [
        'float' => 'string',
    ];
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
