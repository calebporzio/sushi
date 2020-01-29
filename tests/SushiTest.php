<?php

namespace Tests;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;
use Tests\Fixtures\Bar;
use Tests\Fixtures\Foo;

class SushiTest extends TestCase
{
    /**
     * @var string
     */
    private $cachePath;

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
}
