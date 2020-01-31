<?php

namespace Tests;

use Exception;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;
use Tests\Fixtures\Bar;
use Tests\Fixtures\Foo;

class SushiTest extends TestCase
{
    /**
     * @var string
     */
    public $cachePath;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sushi.cache-path' => $this->cachePath = __DIR__.'/cache']);

        $this->cleanUp();
    }

    protected function tearDown(): void
    {
        $this->cleanUp();

        parent::tearDown();
    }

    private function cleanUp()
    {
        Foo::resetStatics();
        File::cleanDirectory($this->cachePath);
    }

    /** @test */
    public function basic_usage()
    {
        $this->assertSame(2, Foo::query()->count());
        $this->assertSame('bar', Foo::first()->foo);
        $this->assertSame('lob', Foo::where('bob', 'lob')->first()->bob);
    }

    /** @test */
    public function not_adding_rows_property_throws_an_error()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Sushi: $rows property not found on model: Tests\Fixtures\Bar');

        Bar::query()->count();
    }

    /** @test */
    public function uses_in_memory_if_the_cache_directory_is_not_writeable_or_not_found()
    {
        config(['sushi.cache-path' => $this->cachePath = __DIR__.'/non-existent-path']);

        Foo::query()->count();

        $this->assertFileNotExists($this->cachePath);
        $this->assertSame(':memory:', (new Foo())->getConnection()->getDatabaseName());
    }

    /** @test */
    public function caches_sqlite_file_if_storage_cache_folder_is_available()
    {
        Foo::query()->count();

        $this->assertFileExists($this->cachePath);

        $expected = str_replace('/', DIRECTORY_SEPARATOR, 'sushi/tests/cache/sushi-tests-fixtures-foo.sqlite');
        $this->assertStringContainsString($expected, (new Foo())->getConnection()->getDatabaseName());
    }

    /** @test */
    public function uses_same_cache_between_requests()
    {
        $this->markTestSkipped("I can't find a good way to test this right now.");
    }
}
