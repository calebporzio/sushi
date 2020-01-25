<?php

namespace Sushi;

use Illuminate\Database\Connectors\ConnectionFactory;

trait Sushi
{
    public static function resolveConnection($connection = null)
    {
        static $cache;

        return $cache ?: $cache = app(ConnectionFactory::class)->make([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    public static function bootSushi()
    {
        $instance = (new static);
        $tableName = $instance->getTable();
        $rows = $instance->rows;
        $firstRow = $rows[0];

        static::resolveConnection()->getSchemaBuilder()->create($tableName, function ($table) use ($firstRow) {
            foreach ($firstRow as $column => $value) {
                if ($column === 'id') {
                    $table->increments('id');
                    continue;
                }

                $type = is_numeric($value) ? 'integer' : 'string';

                $table->{$type}($column);
            }
        });

        static::insert($rows);
    }
}
