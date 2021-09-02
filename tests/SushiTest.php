<?php

namespace Tests;

use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Orchestra\Testbench\TestCase;

class SushiTest extends TestCase
{
    public $cachePath;

    public function setUp(): void
    {
        parent::setUp();

        config(['sushi.cache-path' => $this->cachePath = __DIR__ . '/cache']);

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
    public function basic_usage()
    {
        $this->assertEquals(3, Foo::count());
        $this->assertEquals('bar', Foo::first()->foo);
        $this->assertEquals('lob', Foo::whereBob('lob')->first()->bob);
        $this->assertEquals(2, Bar::count());
        $this->assertEquals('bar', Bar::first()->foo);
        $this->assertEquals('lob', Bar::whereBob('lob')->first()->bob);
    }

    /** @test */
    public function columns_with_varying_types()
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
    public function model_with_custom_schema()
    {
        ModelWithCustomSchema::count();
        $connectionBuilder = ModelWithCustomSchema::resolveConnection()->getSchemaBuilder();
        $this->assertEquals('string', $connectionBuilder->getColumnType('model_with_custom_schemas', 'float'));
        $this->assertEquals('string', $connectionBuilder->getColumnType('model_with_custom_schemas', 'string'));
    }

    /** @test */
    public function models_using_the_get_rows_property_arent_cached()
    {
        Bar::$hasBeenAccessedBefore = false;
        $this->assertEquals(2, Bar::count());
        Bar::resetStatics();
        $this->assertEquals(3, Bar::count());
    }

    /** @test */
    public function uses_in_memory_if_the_cache_directory_is_not_writeable_or_not_found()
    {
        config(['sushi.cache-path' => $path = __DIR__ . '/non-existant-path']);

        Foo::count();

        $this->assertFalse(file_exists($path));
        $this->assertEquals(':memory:', (new Foo)->getConnection()->getDatabaseName());
    }

    /** @test */
    public function caches_sqlite_file_if_storage_cache_folder_is_available()
    {
        Foo::count();

        $this->assertTrue(file_exists($this->cachePath));
        $this->assertStringContainsString(
            'sushi/tests/cache/sushi-tests-foo.sqlite',
            str_replace('\\', '/', (new Foo())->getConnection()->getDatabaseName())
        );
    }

    /** @test */
    public function avoids_error_when_creating_database_concurrently()
    {
        $actualFactory = app(ConnectionFactory::class);
        $actualConnection = $actualFactory->make([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $connectionFactory = $this->createMock(ConnectionFactory::class);
        $connectionFactory->expects($this->once())
            ->method('make')
            ->willReturnCallback(function () use ($actualConnection) {
                // Simulate a concurrent request that creates the table at a point in time
                // where our main execution has already determined that it does not exist
                // and is about to create it.
                $actualConnection->getSchemaBuilder()->create('blanks', function ($table) {
                    $table->increments('id');
                });

                return $actualConnection;
            });

        $this->app->bind(ConnectionFactory::class, function () use ($connectionFactory) {
            return $connectionFactory;
        });

        // Triggers creation of the table
        (new Blank)->getConnection();
    }

    /**
     * @test
     * @group skipped
     * */
    public function uses_same_cache_between_requests()
    {
        $this->markTestSkipped("I can't find a good way to test this right now.");
    }

    /**
     * @test
     * @group skipped
     * */
    public function use_same_cache_between_requests()
    {
        $this->markTestSkipped("I can't find a good way to test this right now.");
    }

    /** @test */
    public function adds_primary_key_if_needed()
    {
        $this->assertEquals([5,6], ModelWithNonStandardKeys::orderBy('id')->pluck('id')->toArray());
        $this->assertEquals(1, Foo::find(1)->getKey());
    }


    /** @test */
    public function it_returns_an_empty_collection()
    {
        $this->assertEquals(0, Blank::count());
    }

    /** @test */
    public function can_use_exists_validation_rule()
    {
        ModelWithNonStandardKeys::all();
        Foo::all();

        $this->assertTrue(Validator::make(['bob' => 'lob'], ['bob' => 'exists:'.ModelWithNonStandardKeys::getSlug().'.model_with_non_standard_keys'])->passes());
        $this->assertTrue(Validator::make(['foo' => 'bar'], ['foo' => 'exists:'.Foo::getSlug().'.foos'])->passes());
        $this->assertTrue(Validator::make(['foo' => 5], ['foo' => 'exists:'.ModelWithNonStandardKeys::getSlug().'.model_with_non_standard_keys,id'])->passes());

        $this->assertFalse(Validator::make(['id' => 4], ['id' => 'exists:'.ModelWithNonStandardKeys::getSlug().'.model_with_non_standard_keys'])->passes());
        $this->assertFalse(Validator::make(['foo' => 'bob'], ['foo' => 'exists:'.Foo::getSlug().'.foos'])->passes());
        $this->assertFalse(Validator::make(['bob' => 'ble'], ['bob' => 'exists:'.ModelWithNonStandardKeys::getSlug().'.model_with_non_standard_keys'])->passes());
    }

    /** @test */
    public function can_trigger_through_relations()
    {
        $this->expectExceptionMessage("Connection refused");

        Qux::find(1)->quz;
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

    public function getRows()
    {
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

class Blank extends Model
{
    use \Sushi\Sushi;

    protected $columns = [
        'id' => 'integer',
        'name' => 'string'
    ];

    protected $rows = [];
}

class Qux extends Model
{
    use \Sushi\Sushi;

    protected $rows = [
        ['id' => 1],
    ];

    public function quz() : HasOne
    {
        return $this->hasOne(Quz::class);
    }
}


class Quz extends Model
{
    public function qux() : BelongsTo
    {
        return $this->belongsTo(Qux::class);
    }
}
