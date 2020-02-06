<?php

namespace Sushi;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Connectors\ConnectionFactory;

trait Sushi
{
    protected static $sushiConnection;

    public static function resolveConnection($connection = null)
    {
        return static::$sushiConnection;
    }

    public static function bootSushi()
    {
        $cachePath = static::getSushiCachePath();
        $modelPath = static::getSushiModelPath();

        $states = [
            'cache-file-found-and-up-to-date' => function () use ($cachePath) {
                static::setSqliteConnection($cachePath);
            },
            'cache-file-not-found-or-stale' => function () {
                static::migrateSushiCache();
            },
            'no-caching-capabilities' => function () {
                static::setSqliteConnection(':memory:');

                (new static)->migrate();
            },
        ];

        if (static::cacheFileFoundAndUpToDate($cachePath, $modelPath)) {
            $states['cache-file-found-and-up-to-date']();
        } else if (static::cacheFileNotFoundOrStale(static::getSushiCacheDirectory())) {
            $states['cache-file-not-found-or-stale']();
        } else {
            $states['no-caching-capabilities']();
        }
    }

    public function migrate()
    {
        $rows = $this->getRows();

        $firstRow = $rows[0];
        $tableName = $this->getTable();

        static::resolveConnection()->getSchemaBuilder()->create($tableName, function ($table) use ($firstRow) {
            if ($this->incrementing && ! in_array($this->primaryKey, $firstRow)) {
                $table->increments($this->primaryKey);
            }

            foreach ($firstRow as $column => $value) {
                $type = is_numeric($value) ? 'integer' : 'string';

                if ($column === $this->primaryKey && $type == 'integer') {
                    $table->increments($this->primaryKey);
                    continue;
                }

                $table->{$type}($column);
            }
        });

        static::insert($rows);
    }

    public static function flushRowCache()
    {
        (new static)->query()->delete();

        static::migrateSushiCache();
    }

    protected function getRows()
    {
        $rows = $this->rows;

        throw_unless(is_array($rows), new \Exception('Sushi: $rows property not found on model: '.get_class($this)));

        return $rows;
    }

    protected static function cacheFileFoundAndUpToDate($cachePath, $modelPath)
    {
        return file_exists($cachePath) && filemtime($modelPath) === filemtime($cachePath);
    }

    protected static function cacheFileNotFoundOrStale($cacheDirectory)
    {
        return file_exists($cacheDirectory) && is_writable($cacheDirectory);
    }

    protected static function setSqliteConnection($database)
    {
        static::$sushiConnection = app(ConnectionFactory::class)->make([
            'driver' => 'sqlite',
            'database' => $database,
        ]);
    }

    private static function migrateSushiCache()
    {
        $cachePath = static::getSushiCachePath();
        $modelPath = static::getSushiModelPath();

        file_put_contents($cachePath, '');

        static::setSqliteConnection($cachePath);

        (new static)->migrate();

        touch($cachePath, filemtime($modelPath));
    }

    private static function getSushiCachePath()
    {
        $cacheFileName = config('sushi.cache-prefix', 'sushi').'-'.Str::kebab(str_replace('\\', '', static::class)).'.sqlite';

        return static::getSushiCacheDirectory().'/'.$cacheFileName;
    }

    private static function getSushiModelPath()
    {
        return (new \ReflectionClass(static::class))->getFileName();
    }

    private static function getSushiCacheDirectory()
    {
        return realpath(config('sushi.cache-path', storage_path('framework/cache')));
    }
}
