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
        $instance = (new static);
        $cacheDirectory = realpath(config('sushi.cache-path', storage_path('framework/cache')));
        $cachePath = static::getSushiCachePath();
        $modelPath = static::getSushiModelPath();

        $states = [
            'cache-file-found-and-up-to-date' => function () use ($cachePath) {
                static::setSqliteConnection($cachePath);
            },
            'cache-file-not-found-or-stale' => function () use ($cachePath, $modelPath, $instance) {
                static::migrateSushiCache();
            },
            'no-caching-capabilities' => function () use ($instance) {
                static::setSqliteConnection(':memory:');

                $instance->migrate();
            },
        ];

        switch (true) {
            case file_exists($cachePath) && filemtime($modelPath) === filemtime($cachePath):
                $states['cache-file-found-and-up-to-date']();
                break;

            case file_exists($cacheDirectory) && is_writable($cacheDirectory):
                $states['cache-file-not-found-or-stale']();
                break;

            default:
                $states['no-caching-capabilities']();
                break;
        }
    }

    protected static function setSqliteConnection($database)
    {
        static::$sushiConnection = app(ConnectionFactory::class)->make([
            'driver' => 'sqlite',
            'database' => $database,
        ]);
    }

    public function migrate()
    {
        $rows = $this->getRows();

        throw_unless(is_array($rows), new \Exception('Sushi: $rows property not found on model: '.get_class($this)));

        $firstRow = $rows[0];
        $tableName = $this->getTable();

        static::resolveConnection()->getSchemaBuilder()->create($tableName, function ($table) use ($firstRow) {
            foreach ($firstRow as $column => $value) {
                $type = is_numeric($value) ? 'integer' : 'string';

                if ($column === 'id' && $type == 'integer') {
                    $table->increments('id');
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
        return $this->rows;
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
        $cacheDirectory = realpath(config('sushi.cache-path', storage_path('framework/cache')));

        return $cacheDirectory.'/'.$cacheFileName;
    }

    private static function getSushiModelPath()
    {
        return (new \ReflectionClass(static::class))->getFileName();
    }
}
